<?php

/**
  * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
  *
  * This file is a part of Codendi.
  *
  * Codendi is free software; you can redistribute it and/or modify
  * it under the terms of the GNU General Public License as published by
  * the Free Software Foundation; either version 2 of the License, or
  * (at your option) any later version.
  *
  * Codendi is distributed in the hope that it will be useful,
  * but WITHOUT ANY WARRANTY; without even the implied warranty of
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with Codendi. If not, see <http://www.gnu.org/licenses/
  */

require_once('mvc/PluginController.class.php');
require_once('GitViews.class.php');
require_once('GitActions.class.php');
require_once('GitRepository.class.php');
/**
 * Git
 * @author Guillaume Storchi
 */
class Git extends PluginController {
    const PERM_READ  = 'PLUGIN_GIT_READ';
    const PERM_WRITE = 'PLUGIN_GIT_WRITE';
    const PERM_WPLUS = 'PLUGIN_GIT_WPLUS';

    /**
     * @var GitRepositoryFactory
     */
    protected $factory;
    
    /**
     * @var UserManager
     */
    private $userManager;
    
    /**
     * @var ProjectManager
     */
    private $projectManager;
    
    public function __construct(GitPlugin $plugin) {
        parent::__construct();
        
        $this->userManager = UserManager::instance();
        
        $this->factory = new GitRepositoryFactory();
        $this->projectManager = ProjectManager::instance();
        
        $matches = array();
        if ( preg_match_all('/^\/plugins\/git\/index.php\/(\d+)\/([^\/][a-zA-Z]+)\/([a-zA-Z\-\_0-9]+)\/\?{0,1}.*/', $_SERVER['REQUEST_URI'], $matches) ) {
            $this->request->set('group_id', $matches[1][0]);
            $this->request->set('action', $matches[2][0]);
            $repo_id = 0;            
            //repository id is passed            
            if ( preg_match('/^([0-9]+)$/', $matches[3][0]) === 1 ) {
               $repo_id = $matches[3][0];
            } else {
            //get repository by name and group id to retrieve repo id
               $repo = new GitRepository();
               $repo->setName($matches[3][0]);
               $repo->setProject( $this->projectManager->getProject($matches[1][0]) );
               try {
                   $repo->load();
               } catch (Exception $e) {                   
                   $this->addError('Bad request');
                   $this->redirect('/');                   
               }
               $repo_id = $repo->getId();               
            }
            $this->request->set('repo_id', $repo_id);
        }        
        $this->plugin      = $plugin;
        $valid = new Valid_GroupId('group_id');
        $valid->required();
        if($this->request->valid($valid)) {
            $this->groupId = (int)$this->request->get('group_id');
        }
        $valid = new Valid_String('action');
        $valid->required();
        if($this->request->valid($valid)) {
            $this->action = $this->request->get('action');
        }

        if (  empty($this->action) ) {
            $this->action = 'index';
        }                  
        if ( empty($this->groupId) ) {            
            $this->addError('Bad request');
            $this->redirect('/');
        }
      
        $this->projectName      = $this->projectManager->getProject($this->groupId)->getUnixName();
        if ( !PluginManager::instance()->isPluginAllowedForProject($this->plugin, $this->groupId) ) {
            $this->addError( $this->getText('project_service_not_available') );
            $this->redirect('/projects/'.$this->projectName.'/');
        }

        $this->permittedActions = array();
    }
    
    public function setProjectManager($projectManager) {
        $this->projectManager = $projectManager;
    }

        
    public function setFactory(GitRepositoryFactory $factory) {
        $this->factory = $factory;
    }
    /**
     * Added for testing
     */
    public function _addInstanceVars($request, $userManager, $action = null, $permittedActions = null, $groupId = null) {
        $this->request = $request;
        $this->userManager = $userManager;
        $this->action = $action;
        $this->permittedActions = $permittedActions;
        $this->groupId = $groupId;
    }

    protected function getText($key, $params = array()) {
        return $GLOBALS['Language']->getText('plugin_git', $key, $params);
    }
    
    protected function definePermittedActions($repoId, $user) {
        if ( $this->user->isMember($this->groupId, 'A') === true ) {
            $this->permittedActions = array('index',
                                            'view' ,
                                            'edit',
                                            'clone',
                                            'add',
                                            'del',
                                            'create',
                                            'confirm_deletion',
                                            'save',
                                            'repo_management',
                                            'mail_prefix',
                                            'add_mail',
                                            'remove_mail',
                                            'fork',
                                            'set_private',
                                            'confirm_private',
                                            'fork_repositories',
                                            'do_fork_repositories',
            );
        } else {
            $this->addPermittedAction('index');
            if ($this->user->isMember($this->groupId)) {
                $this->addPermittedAction('fork_repositories');
                $this->addPermittedAction('do_fork_repositories');
            }
            
            if ($repoId !== 0) {
                $repo = new GitRepository();
                $repo->setId($repoId);
                if ($repo->exists() && $repo->userCanRead($user)) {
                    $this->addPermittedAction('view');
                    $this->addPermittedAction('edit');
                    $this->addPermittedAction('clone');
                    if ($repo->belongsTo($user)) {
                        $this->addPermittedAction('repo_management');
                        $this->addPermittedAction('mail_prefix');
                        $this->addPermittedAction('add_mail');
                        $this->addPermittedAction('remove_mail');
                        $this->addPermittedAction('del');
                        $this->addPermittedAction('confirm_deletion');
                        $this->addPermittedAction('save');
                    }
                }
            }
        }
    }

    public function request() {
        $valid = new Valid_String('repo_name');
        $valid->required();
        $repositoryName = null;
        if($this->request->valid($valid)) {
            $repositoryName = trim($this->request->get('repo_name'));
        }
        $valid = new Valid_UInt('repo_id');
        $valid->required();
        if($this->request->valid($valid)) {
            $repoId = $this->request->get('repo_id');
        } else {
            $repoId = 0;
        }

        $user = $this->userManager->getCurrentUser();

        //define access permissions
        $this->definePermittedActions($repoId, $user);

        //check permissions
        if ( empty($this->permittedActions) || !$this->isAPermittedAction($this->action) ) {
            $this->addError($this->getText('controller_access_denied'));
            $this->redirect('/plugins/git/?group_id='.$this->groupId);
            return;
        }

        $this->_informAboutPendingEvents($repoId);
        $this->_dispatchActionAndView($this->action, $repoId, $repositoryName, $user);

    }
    
    public function _dispatchActionAndView($action, $repoId, $repositoryName, $user) {
        switch ($action) {
            #CREATE REF
            case 'create':
                $this->addView('create');
                break;
            #admin
            case 'view':
                $this->addAction( 'getRepositoryDetails', array($this->groupId, $repoId) );                
                $this->addView('view');
                break;
           
            #ADD REF
            case 'add':
                $this->addAction('createReference', array($this->groupId, $repositoryName) );
                $this->addView('index');
                break;
             #DELETE a repository
            case 'del':                
                $this->addAction( 'deleteRepository', array($this->groupId, $repoId) );
                $this->addView('index');
                break;
            #EDIT
            case 'edit':                
                if ( $this->isAPermittedAction('clone') && $this->request->get('clone') ) {
                    $valid = new Valid_UInt('parent_id');
                    $valid->required();
                    if($this->request->valid($valid)) {
                        $parentId = (int)$this->request->get('parent_id');
                    }
                    $this->addAction( 'cloneRepository', array($this->groupId, $repositoryName, $parentId) );
                    $this->addAction( 'getRepositoryDetails', array($this->groupId, $parentId) );
                    $this->addView('view');
                }
                else if ( $this->isAPermittedAction('confirm_deletion') && $this->request->get('confirm_deletion') ) {
                    $this->addAction('confirmDeletion', array($this->groupId, $repoId) );
                    $this->addView('confirm_deletion', array( 0=>array('repo_id'=>$repoId) ) );
                }
                else if ( $this->isAPermittedAction('save') && $this->request->get('save') ) {                    
                    $valid = new Valid_Text('repo_desc');
                    $valid->required();
                    if($this->request->valid($valid)) {
                        $repoDesc = $this->request->get('repo_desc');
                    }
                    $valid = new Valid_String('repo_access');
                    $valid->required();
                    if($this->request->valid($valid) || is_array($this->request->get('repo_access'))) {
                        $repoAccess = $this->request->get('repo_access');
                    }
                    $this->addAction('save', array($this->groupId, $repoId, $repoAccess, $repoDesc) );
                    $this->addView('view');
                } else {
                    $this->addError( $this->getText('controller_access_denied') );
                    $this->redirect('/plugins/git/?group_id='.$this->groupId);
                }
                break;
            #repo_management
            case 'repo_management':
                $this->addAction('repoManagement', array($this->groupId, $repoId));
                $this->addView('repoManagement');
                break;
            #mail prefix
            case 'mail_prefix':
                $valid = new Valid_String('mail_prefix');
                $valid->required();
                if($this->request->valid($valid)) {
                    $mailPrefix = $this->request->get('mail_prefix');
                } else {
                    $mailPrefix = '';
                }
                $this->addAction('notificationUpdatePrefix', array($this->groupId, $repoId, $mailPrefix));
                $this->addView('repoManagement');
                break;
            #add mail
            case 'add_mail':
                $validMails = array();
                $mails      = array_map('trim', preg_split('/[,;]/', $this->request->get('add_mail')));
                $rule       = new Rule_Email();
                $um         = UserManager::instance();
                foreach ($mails as $mail) {
                    if ($rule->isValid($mail)) {
                        $validMails[] = $mail;
                    } else {
                        $user = $um->findUser($mail);
                        if ($user) {
                            $mail = $user->getEmail();
                            if ($mail) {
                                $validMails[] = $mail;
                            } else {
                                $this->addError($this->getText('no_user_mail', array($mail)));
                            }
                        } else {
                            $this->addError($this->getText('no_user', array($mail)));
                        }
                    }
                }
                $this->addAction('notificationAddMail', array($this->groupId, $repoId, $validMails));
                $this->addView('repoManagement');
                break;
            #remove mail
            case 'remove_mail':
                $mails = array();
                $valid = new Valid_Email('mail');
                $valid->required();
                if($this->request->validArray($valid)) {
                    $mails = $this->request->get('mail');
                }
                if (count($mails) > 0) {
                    $this->addAction('notificationRemoveMail', array($this->groupId, $repoId, $mails));
                    $this->addView('repoManagement');
                } else {
                    $this->addAction('repoManagement', array($this->groupId, $repoId));
                    $this->addView('repoManagement');
                }
                break;
            #fork
            case 'fork':
                $this->addAction('repoManagement', array($this->groupId, $repoId));
                $this->addView('fork');
                break;
            #confirm_private
            case 'confirm_private':
                if ( $this->isAPermittedAction('confirm_deletion') && $this->request->get('confirm_deletion') ) {
                    $this->addAction('confirmDeletion', array($this->groupId, $repoId) );
                    $this->addView('confirm_deletion', array( 0=>array('repo_id'=>$repoId) ) );
                }
                else if ( $this->isAPermittedAction('save') && $this->request->get('save') ) {
                    $valid = new Valid_Text('repo_desc');
                    $valid->required();
                    if($this->request->valid($valid)) {
                        $repoDesc = $this->request->get('repo_desc');
                    }
                    $valid = new Valid_String('repo_access');
                    $valid->required();
                    if($this->request->valid($valid)) {
                        $repoAccess = $this->request->get('repo_access');
                    }
                    $this->addAction('confirmPrivate', array($this->groupId, $repoId, $repoAccess, $repoDesc) );
                    $this->addView('confirmPrivate');
                }
                break;
             #SET TO PRIVATE
            case 'set_private':
                $this->addAction('setPrivate', array($this->groupId, $repoId));
                $this->addView('view');
                break;
            case 'fork_repositories':
                $this->addAction('getProjectRepositoryList', array($this->groupId));
                $this->addView('forkRepositories');
                break;
            case 'do_fork_repositories':
                try {
                    $this->_doDispatchForkRepositories($this->request, $user);
                } catch (MalformedPathException $e) {
                    $this->addError($this->getText('fork_malformed_path'));
                }
                $this->addView('forkRepositories');
                break;
            case 'fork_cross_project': 
                $this->_doDispatchForkCrossProject($this->request, $user);
                $this->addView('forkRepositories');
                break;
            #LIST
            default:
                
                $user_id = null;
                $valid = new Valid_UInt('user');
                $valid->required();
                if($this->request->valid($valid)) {
                    $user_id = $this->request->get('user');
                    $this->addData(array('user' => $user_id));
                }
                $this->addAction( 'getProjectRepositoryList', array($this->groupId, $user_id) );
                $this->addView('index');
                break;
        }
        
        
    }

    protected function _informAboutPendingEvents($repoId) {
        $sem = SystemEventManager::instance();
        $dar = $sem->_getDao()->searchWithParam('head', $this->groupId, array('GIT_REPO_CREATE', 'GIT_REPO_CLONE', 'GIT_REPO_DELETE'), array(SystemEvent::STATUS_NEW, SystemEvent::STATUS_RUNNING));
        foreach ($dar as $row) {
            switch($row['type']) {
            case 'GIT_REPO_CREATE':
                $p = explode('::', $row['parameters']);
                $GLOBALS['Response']->addFeedback('info', $this->getText('feedback_event_create', array($p[1])));
                break;

            case 'GIT_REPO_CLONE':
                $p = explode('::', $row['parameters']);
                $GLOBALS['Response']->addFeedback('info', $this->getText('feedback_event_fork', array($p[1])));
                break;

            case 'GIT_REPO_DELETE':
                $GLOBALS['Response']->addFeedback('info', $this->getText('feedback_event_delete'));
                break;
            }
            
        }

        if ($repoId !== 0) {
            $dar = $sem->_getDao()->searchWithParam('head', $repoId, array('GIT_REPO_ACCESS'), array(SystemEvent::STATUS_NEW, SystemEvent::STATUS_RUNNING));
            foreach ($dar as $row) {
                $GLOBALS['Response']->addFeedback('info', $this->getText('feedback_event_access'));
            }
        }
    }
    
    /**
     * Instantiate an action based on a given name.
     *
     * Can be overriden to pass additionnal parameters to the action
     *
     * @param string $action The name of the action
     *
     * @return PluginActions
     */
    protected function instantiateAction($action) {
        return new $action($this, SystemEventManager::instance());
    }

    public function _doDispatchForkCrossProject($request, $user) {
        $validators = array(new Valid_UInt('to_project'), new Valid_Array('repos'));
        foreach ($validators as $validator) {
            $validator->required();
            if (!$request->valid($validator)) {
                $this->addError($this->getText('missing_parameter', array($validator->key)));
                $this->redirect('/plugins/git?group_id='.$this->groupId);
                return;
            }
        }
        $toProjectId = $request->get('to_project');
        $to_project = $this->projectManager->getProject($toProjectId);
        $repoIds = $request->get('repos');
        $repos = array();
        foreach ($repoIds as $id) {
            $repos[] = $this->factory->getRepository($this->groupId, $id);
        }
        $this->addAction('forkCrossProject', array($this->groupId, $repos, $to_project, $user, $GLOBALS['HTML']));
    }

    public function _doDispatchForkRepositories($request, $user) {
        $this->addAction('getProjectRepositoryList', array($this->groupId));
        $token = new CSRFSynchronizerToken('/plugins/git/?group_id='. (int)$this->groupId .'&action=fork_repositories');
        $token->check();

        $repos_ids = array();

        $valid = new Valid_String('path');
        $valid->required();

        $path      = '';
        if($request->valid($valid)) {
            $path = trim($request->get('path'));
        }
        $path = userRepoPath($user->getUserName(), $path);

        $valid = new Valid_UInt('repos');
        $valid->required();
        if($request->validArray($valid)) {
            $repos_ids = $request->get('repos');
        }

        $this->addAction('forkRepositories', array($this->groupId, $repos_ids, $path, $user, $GLOBALS['HTML']));
        
    }
}

?>
