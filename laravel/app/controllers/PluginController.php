<?php

use BattleTools\UserManagement\UserSettings;
use BattleTools\Util\Deploy;
use BattleTools\Util\Jenkins;

class PluginController extends BaseController {

    public function __construct(){
        $this->beforeFilter('auth.administrator', array('except'=>array('getPlugins','managePlugins')));
    }

    public function getPlugins(){
        $vars['title'] = "Our Plugins";

        $vars['plugins'] = DB::table("plugins")->get();

        foreach($vars['plugins'] as $plugin){
            $authors[$plugin->author] = UserSettings::getUsernameFromId($plugin->author);
        }

        $vars['authors'] = $authors;

        foreach($vars['plugins'] as $plugin){
            $ci = Jenkins::getLatestBuild("http://ci.battleplugins.com", $plugin->name);
            $builds[] = array(
                'ci' => $ci,
                'name' => $plugin->name,
                'author' => $plugin->author,
                'bukkit' => $plugin->bukkit
            );
        }

        arsort($builds);

        $vars['builds'] = $builds;

        parent::setActive("Resources");

        return View::make('plugins', $vars);
    }

    public function managePlugins(){
        if(Deploy::isDeveloperMode()){
            $vars['title'] = 'Want to create plugins with us?';
            parent::setActive('Plugins');

            return View::make('developer.managePlugins', $vars);
        }else{
            return Redirect::to("http://wiki.battleplugins.com/w/index.php?title=BA_API_Tutorial");
        }
    }
}