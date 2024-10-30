<?php
/*
  Plugin Name: Click to donate
  Description: Provides a system for managing "click to donate" campaigns based on user visits and clicks
  Version: 1.0.5
  Plugin URI: http://code.google.com/p/click-to-donate-for-wordpress/
  Author: Cláudio Esperança, Diogo Serra
  Author URI: http://dei.estg.ipleiria.pt/
 */
/*
    Copyright 2012  Cláudio Esperança, Diogo Serra

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; If not, see <http://www.gnu.org/licenses/>.
*/

if (!class_exists('ClickToDonate')):
    class ClickToDonate {
        // Store the location of the current file in a constant to use by the child modules
        const FILE = __FILE__;
        
        // Store the class name 
        const CLASS_NAME = __CLASS__;
    
        /**
         * Autoload static method for loading classes and interfaces.
         *
         * @param string $className The name of the class or interface.
         * @return void
         */
        public static function autoload($className=''){
            if(!empty($className)):
                // For each library path in the include path
                $libraryPaths = explode(PATH_SEPARATOR, get_include_path());
                foreach($libraryPaths as $libraryPath):
                    $file = "{$libraryPath}/{$className}.php";
                    // Check if the file we are trying to load belongs to us
                    if(is_readable($file) && strpos(dirname(realpath($file)), realpath(plugin_dir_path(__FILE__)))===0):
                        // Load the file
                        include_once($file);
                        // If the class exists...
                        if(class_exists($className)):
                            $oClass = new ReflectionClass($className);
                            // ... and the init method, call it
                            if($oClass->hasMethod('init')):
                                $reflectionMethod = new ReflectionMethod($className, 'init');
                                $reflectionMethod->invoke();
                            
                                // Terminate the class loading
                                return;
                            endif;
                        endif;
                    endif;
                endforeach;
            endif;
        }

        /**
         * Instanciate and load the class
         * @param <string> $className
         */
        public static function loadClass($className=''){
            if(!empty($className)):
                new $className();
            endif;
        }

        /**
         * Instanciate and load all the classes
         * @param <array> $classesName
         */
        public static function loadClasses($classesName=array()){
            if(!empty($classesName) && is_array($classesName)):
                foreach($classesName as $className):
                    self::loadClass($className);
                endforeach;
            endif;
        }

        /**
         * Automagically load the files to the plugin
         *
         * @param string $dir, with the directory name to start the loading from
         * @param [string $thatMatch], with the sufix to compare with the filename to load
         * @param int $recursiveCount with the number of subdirectories to load
         * @version 4.0
         */
        public static function autoLoadFrom($dir, $thatMatch='/.php$/', $recursiveCount=0){
            $dircontents = scandir($dir);
            foreach ($dircontents as $content):
                if($content!="." && $content!=".."):
                    if(preg_match($thatMatch, strtolower($content))>0 && is_file("{$dir}/{$content}")):
                        self::getFile("{$dir}/{$content}");
                    endif;
                    if($recursiveCount>0 && is_dir("{$dir}/{$content}")):
                        self::autoLoadFrom("{$dir}/{$content}", $thatMatch, $recursiveCount-1);
                    endif;
                endif;
            endforeach;
        }

        /**
         * Load the file if it is on the plugin dir
         * @param string $file with the filepath to load
         * @return boolean true on success, false otherwise
         * @version 3.0
         */
        public static function getFile($file){
            if(is_readable($file) && dirname(__FILE__) && stripos(dirname(realpath($file)),dirname(__FILE__))===0):
                if(require_once($file)):
                    return true;
                endif;
            endif;
            //error_log("Unable to load the file {$file}");

            return false;
        }
        

        /**
         * Add custom plugin library paths to the include path
         * 
         * @param <array> or <string> $libraryPath to add
         * @return <void>
         */
        public static function addLibraryPaths($libraryPath=''){
            if(empty($libraryPath)): // Empty library path, so cancel the operation
                return;
            elseif(is_array($libraryPath)): // If an array, remove all the prohibited items and unlock the next step
                $allowedPath = false;
                foreach($libraryPath as $key=>$singleLibraryPath):
                    if(!is_dir($singleLibraryPath) || strpos($singleLibraryPath, plugin_dir_path(__FILE__))!==0):
                        unset($libraryPath[$key]);
                    elseif(!$allowedPath):
                        $allowedPath=true;
                    endif;
                endforeach;
            else: // If is a string and is in the allowed path, unlock the next step
                $allowedPath = (is_dir($libraryPath) && strpos($libraryPath, plugin_dir_path(__FILE__))===0);
            endif;

            if(!empty($libraryPath) && $allowedPath): // If we have items to add, verify such items doesn't exist and add them
                $libraryPaths = explode(PATH_SEPARATOR, get_include_path());
                if(!in_array($libraryPath, $libraryPaths)):
                    set_include_path((is_array($libraryPath)?implode(PATH_SEPARATOR, array_unique($libraryPath)):$libraryPath).PATH_SEPARATOR.get_include_path());
                endif;
            endif;
        }

        /**
         * Module initialization
         */
        public static function init(){
            // Register the autoload method
            //spl_autoload_extensions(spl_autoload_extensions().',.load.php');
            spl_autoload_register(array(__CLASS__, 'autoload'));
            
            // Library directory
            $dir = dirname(__FILE__).'/php/';
            
            // Define the module file directory
            self::addLibraryPaths($dir);
            
            // Autoload the all the PHP files in the library directory
            self::autoLoadFrom($dir);
        }
    }

    endif;

ClickToDonate::init();