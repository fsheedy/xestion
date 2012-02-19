<?php

/*
 *  xestion.php
 *
 *  Copyright (C) 2012  Frédéric Sheedy <sheedy@kde.org>
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * This is the file description
 *
 * @author Frédéric Sheedy <sheedy@kde.org>
 */

require_once 'scurvy.php';

class Xestion {

    private $languageCode               = 'fr';
    private $messageGroupsUrl           = 'http://userbase.kde.org/api.php?action=query&meta=messagegroups&format=json';
    private $messageGroupBaseUrl        = 'http://userbase.kde.org/index.php?title=Special%%3ATranslate&task=export-as-po&group=%1$s&language=%2$s&limit=200';
    private $pageBaseUrl                = 'http://userbase.kde.org/index.php?title=Special:Translate&task=view&group=%1$s&language=%2$s';
    private $pologyBaseUrl              = '../pology-errors.php?po=%s&package=userbase.kde.org&mode=web';
    private $messageGroupBasePOFileName = 'POFiles/%s.po';
    private $HTMLFileName               = 'pofiles.php';
    
    // PODirectory should end with /
    private $PODirectory = 'POFiles/';
    private $pologyPath = '~/pology/scripts/posieve.py';
    
    /**
     * Initial setup
     *
     */
    function __construct($languageCode, $PODirectory, $pologyPath) {
        $this->langueCode = $languageCode;
        $this->PODirectory = $PODirectory;
        $this->pologyPath = $pologyPath;
    }

    /**
     * Generate PO files
     *
     */
    function generatePo() {

        // Header
        echo "\n\n*** GENERATE userbase.kde.org PO FILES"."\n";
        
        // Get and decode JSON
        echo "Get MessageGroups from JSON\n";
        $MessageGroupsJson = file_get_contents($this->messageGroupsUrl);
        $MessageGroupsJsonObject = json_decode($MessageGroupsJson);
        $MessageGroupsJsonArray = json_decode($MessageGroupsJson, true);

        // Parse JSON
        $MessageGroup = 0;
        $MessageGroupTotal = count($MessageGroupsJsonArray['query']['messagegroups']);
        foreach ( $MessageGroupsJsonObject->query->messagegroups as $messageGroup ) {
            
            // Ignore the page with id 'page-0-all'
            if($messageGroup->id == 'page-0-all') {
                $MessageGroup++;
                echo "Get page $MessageGroup/$MessageGroupTotal :  {$messageGroup->label} (page ignored!)"."\n";
                continue;
            }
            
            $MessageGroup++;
            echo "Get page $MessageGroup/$MessageGroupTotal :  {$messageGroup->label}"."\n";

            // Get PO file
            $messageGroupURL = sprintf($this->messageGroupBaseUrl, str_replace(' ', '+', $messageGroup->id), $this->languageCode);
            $messageGroupPOFileContent = file_get_contents($messageGroupURL);
            
            // Generate PO file
            $messageGroupFileName = str_replace('/', '_', $messageGroup->id);
            $messageGroupFileName = str_replace(' ', '+', $messageGroupFileName);
            $messageGroupPOFileName = sprintf($this->messageGroupBasePOFileName, $messageGroupFileName);
            $messageGroupPOFile = fopen($messageGroupPOFileName, 'w');

            if (is_writable($messageGroupPOFileName)) {
                if (fwrite($messageGroupPOFile, $messageGroupPOFileContent) === FALSE) {
                  // TODO: explicit error message
                  echo "Not writable ($messageGroupPOFile)"."\n";
                  exit;
                }

                fclose($messageGroupPOFile);
            } else {
                // TODO: explicit error message
                echo "Not writable."."\n";
            }

        }
    }
    
    /**
     * Call pologyCheck
     *
     */
    function pologyCheck() {
        
        echo "\n\n*** Check rules with pology and aspell"."\n";
        
        // Delete cache
        // TODO: ask for deletion
        exec('rm -f ~/.pology-check_rules-cache/*.po');
        
        // Run rules and aspell
        exec('python '.$this->pologyPath.' -sxml:pology-rules-errors.xml check_rules');
        exec('python '.$this->pologyPath.' -sxml:pology-spell-errors.xml check_spell');
    }
    
    /**
     * Generate HTML results page
     *
     */
    function generateHTMLPage() {
        
        $files = array();
        $template = new Scurvy('pofiles.php', __DIR__.'/../templates/');
        
        // Get all PO Files
        if ($POFileList = scandir($this->PODirectory)) {
            $rulesErrors = simplexml_load_file('pology-rules-errors.xml', 'SimpleXMLElement', LIBXML_NOCDATA);
            $spellErrors = simplexml_load_file('pology-spell-errors.xml', 'SimpleXMLElement', LIBXML_NOCDATA);
            $fileCount = count($POFileList);
            $currentFile = 0;
            
            foreach ($POFileList as $file) {
                
                $currentFileShow = $currentFile + 1;
                echo "Process page $currentFileShow of $fileCount";
                
                if (is_file($this->PODirectory.$file)) {
                    // Get status
                    $files[$currentFile]['POFilename'] = $this->PODirectory.$file;
                    $POStatus = exec('python '.$this->pologyPath.' stats -smsgbar "' . $files[$currentFile]['POFilename'] . '"');
                    $POStatus = str_replace('-', '0', $POStatus);
                    $POStatus = preg_replace('# msgs \|(.*)#', '', $POStatus);
                    $POStatus = split('/', $POStatus);
                    $files[$currentFile]['POStatus'] = $POStatus;
                    $files[$currentFile]['translated'] = (int)$POStatus[0];
                    $files[$currentFile]['fuzzy'] = (int)$POStatus[1];
                    $files[$currentFile]['untranslated']  = (int)$POStatus[2];
                    $files[$currentFile]['total'] = $files[$currentFile]['translated'] + $files[$currentFile]['fuzzy'] + $files[$currentFile]['untranslated'];
                    $files[$currentFile]['translatedPC'] = round(($files[$currentFile]['translated']/$files[$currentFile]['total']) * 100);
                    $files[$currentFile]['fuzzyPC'] = round(($files[$currentFile]['fuzzy']/$files[$currentFile]['total']) * 100);
                    $files[$currentFile]['untranslatedPC'] = round(($files[$currentFile]['untranslated']/$files[$currentFile]['total']) * 100);
                    $fileDisplayName = ltrim($file, 'page|');
                    $fileDisplayName = rtrim($fileDisplayName, '.po');
                    $fileDisplayName = str_replace('_', '/', $fileDisplayName);
                    $files[$currentFile]['fileDisplayName'] = str_replace('+', ' ', $fileDisplayName);
                    
                    // Calculate rules and spell errors
                    $errorsCount = 0;
                    foreach ($rulesErrors->po as $po) {
                        if ($po["name"] == $file) {
                            $errorsCount = count($po);
                        }
                    }
                    foreach ($spellErrors->po as $po) {
                        if ($po["name"] == $file) {
                            $errorsCount += count($po);
                        }
                    }
                    
                    $files[$currentFile]['errorsCount'] = $errorsCount;
                    
                    $pageURL = rtrim($file, '.po');
                    $pageURL = str_replace('_', '%2F', $file);
                    $pageURL = sprintf($this->pageBaseUrl, $file, $this->languageCode);
                    $files[$currentFile]['pageURL'] = $pageURL;
                    
                    $pologyURL = sprintf($this->pologyBaseUrl, $file);
                    $files[$currentFile]['pologyURL'] = $pologyURL;
                    
                }
                $currentFile++;
            }
            $template->set('file', $files);
            $template->set('udapteDate', $rulesErrors["date"]);
            $HTMLFileFinalContent =  $template->render();
           
            $HTMLFile = fopen($this->HTMLFileName, 'w');

            if (is_writable($this->HTMLFileName)) {
                if (fwrite($HTMLFile, $HTMLFileFinalContent) === FALSE) {
                  // TODO: explicit error message
                  echo "Unable to write HTML file ($HTMLFile)"."\n";
                  exit;
                }

                fclose($HTMLFile);
            } else {
                // TODO: explicit error message
                echo "Unable to write HTML file."."\n";
            }
        } else {
            // TODO: explicit error message
            echo 'Unable to scan directory';
        }
    }

}
