<?php

namespace Controllers;

use Utils\Uri\Uri;
use Controllers\PageLayout\MainLayoutController;
use lib\Objects\User\UserPreferences\UserPreferences;
use lib\Objects\User\UserPreferences\UserMapSettings;
use Utils\Debug\Debug;
use lib\Objects\User\User;

class CacheMapController extends BaseController
{

    public function __construct()
    {
        parent::__construct();

        // map is only for logged users
        $this->redirectNotLoggedUsers();
    }

    public function isCallableFromRouter($actionName)
    {
        return true;
    }

    public function index()
    {
        $this->fullScreeenMap();
    }

    /**
     * Display fullscreen map
     */
    public function fullScreen($debug=false)
    {
        $this->view->setTemplate('cacheMap/mapFullScreen');
        $this->view->setVar('embded', false);

        $this->mapCommonInit($debug);

        $this->view->display(MainLayoutController::MINI_TEMPLATE);
    }

    public function embeded($debug=false)
    {
        $this->view->setTemplate('cacheMap/mapEmbeded');
        $this->view->setVar('embded', true);

        $this->mapCommonInit($debug);

        $this->view->buildView();
    }

    private function mapCommonInit($debug)
    {
        $this->view->loadJQuery();
        $this->view->addLocalCss(
            Uri::getLinkWithModificationTime('/tpl/stdstyle/cacheMap/cacheMap.css'));


        $this->view->addHeaderChunk('openLayers', [$debug]);
        $this->view->addLocalJs(
            Uri::getLinkWithModificationTime('/tpl/stdstyle/cacheMap/mapv4Common.js'));


        // read map config + run keys injector
        $mapConfigArray = $this->ocConfig->getMapConfig();
        $mapConfigInitFunc = $mapConfigArray['keyInjectionCallback'];
        if( !$mapConfigInitFunc($mapConfigArray) ){
            Debug::errorLog('mapConfig init failed');
            exit;
        }
        $this->view->setVar('extMapConfigs',$mapConfigArray['jsConfig']);

        // find user for this map display
        $user = null;
        if(isset($_REQUEST['userid'])){
            $user = User::fromUserIdFactory($_REQUEST['userid']);
        }
        if(!$user){
            $user = $this->loggedUser;
        }
        $this->view->setVar('mapUserId', $user->getUserId());
        $this->view->setVar('mapUserName', $user->getUserName());

        // parse searchData if given
        if( isset($_REQUEST['searchdata'])){
            $this->view->setVar('searchData', $_REQUEST['searchdata']);
        }

        // parse powerTrailIds if given
        if( isset($_REQUEST['pt'])){
            $this->view->setVar('powerTrailIds', $_REQUEST['pt']);
        }




        $userPref = UserPreferences::getUserPrefsByKey(UserMapSettings::KEY);
        $this->view->setVar('filterVal', $userPref->getJsonValues());

    }

    public function saveMapSettingsAjax()
    {
        if(!isset($_POST['userMapSettings'])){
            $this->ajaxErrorResponse('no filtersData var in JSON', 400);
        }

        $json = $_POST['userMapSettings'];

        if(UserPreferences::savePreferencesJson(UserMapSettings::KEY, $json)){
            $this->ajaxSuccessResponse("Data saved");
        }else{
            $this->ajaxErrorResponse("Can't save a data", 500);
        }

    }
}
