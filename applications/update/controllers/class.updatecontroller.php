<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class UpdateController extends Gdn_Controller {

   public $UpdateModule = NULL;
   public $Latest = NULL;
   
   public function __construct() {
      parent::__construct();
   }
   
   public function Initialize() {
      $this->Permission('Garden.Settings.Manage');
      parent::Initialize();
      
      $this->Update = new VanillaUpdateModel();
      if (!$this->Update->Active())
         $this->Update->Fresh();
      
      // Do automatic things only if we're accessing with DELIVERY_TYPE_ALL
      if ($this->DeliveryType() == DELIVERY_TYPE_ALL) {
      
         $Next = $this->Update->Action();
         
         // Redirect if we're on the wrong page
         if (is_string($Next)) {
            $WhereAmI = Gdn::Request()->Path();
            
            if (substr($WhereAmI, 0, 6) != 'update')
               $WhereAmI = CombinePaths(array('update',$WhereAmI));
            
            if ($Next != substr($WhereAmI,0,strlen($Next))) {
            
               // Add request args to the redirect
               $Final = CombinePaths(array_merge(array($Next),$this->RequestArgs));
               
               Gdn::Dispatcher()->Dispatch($Final);
               exit;
            }
         }
      }
      
      $this->Head = new HeadModule($this);
      $this->AddJsFile('jquery.js');
      $this->AddJsFile('jquery.livequery.js');
      $this->AddJsFile('jquery.form.js');
      $this->AddJsFile('jquery.popup.js');
      $this->AddJsFile('jquery.gardenhandleajaxform.js');
      $this->AddJsFile('global.js');
      
      $this->AddJsFile('updater.js');
      $this->AddCssFile('update.css');
      
      $this->MasterView = 'update';
      
      $ApplicationManager = new Gdn_ApplicationManager();
      $this->EnabledApplications = $ApplicationManager->EnabledVisibleApplications();
      $this->UpdaterApplication = GetValue('Update', $this->EnabledApplications);
      $this->UpdaterVersion = GetValue('Version', $this->UpdaterApplication);
      
      $this->UpdateModule = new UpdateModule($this);
      $this->UpdateModule->GetData($this->Update);
      $this->AddModule($this->UpdateModule);
   }
   
   public function Menu() {
      if (!$this->Update->Active()) return;
      
      echo $this->UpdateModule->ToString();
      $this->Render('blank','update');
   }
   
   protected function RequestType() {
      list($Type) = $this->RequestArgs;
      $Type = strtolower($Type);
      if (in_array($Type, array('ui','perform','check'))) return $Type;
      return 'ui';
   }
   
   protected function CheckStatus() {
      // Determine if an update is required.
      $CurrentVersion = APPLICATION_VERSION;
      
      // Require a certain baseline version to start from... this way we're assured some form of basic functionality
/*
      if (version_compare($CurrentVersion, '2.1') < 0)
         return FALSE;
*/
      
      $LatestVersion = $this->GetLatestVersionNumber();
      
      return version_compare($CurrentVersion, $LatestVersion);
   }
   
   protected function GetLatestVersion() {
      if (is_null($this->Latest)) {
         $Repository = C('Update.Remote.Repository');
         $Addon = C('Update.Remote.Addon');
         $APICall = C('Update.Remote.APICall');
         $Request = CombinePaths(array($Repository,$APICall,$Addon));
         
         $this->Latest = simplexml_load_file($Request);
         if ($this->Latest === FALSE)
            throw new Exception("Unable to contact remote addon repository at '{$Request}'.");
      }
      
      $Versions = sizeof($this->Latest->Versions->Item);
      if (!$Versions) return FALSE;
      $LatestVersion = (array)$this->Latest->Versions->Item;
      return $LatestVersion;
   }
   
   protected function GetLatestVersionNumber() {
      $LatestVersion = $this->GetLatestVersion();
      $LatestVersionNumber = GetValue('Version', $LatestVersion, 'COULD NOT FIND');
      return $LatestVersionNumber;
   }
   
}