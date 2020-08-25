<?php

namespace Hawky;

use \Hawky\Set as HawkySet;

trait YAML {

    // Return settings from text
    function yamlget ($text, $blockStart) {
        $settings = new HawkySet();
        if (empty($blockStart)) {
            foreach (getTextLines($text) as $line) {
                if (preg_match("/^\#/", $line)) continue;
                if (preg_match("/^\s*(.*?)\s*:\s*(.*?)\s*$/", $line, $matches)) {
                    if (!empty($matches[1]) && !is_empty($matches[2])) {
                        $settings[$matches[1]] = $matches[2];

                    }
                }
            }
        } else {
            $blockKey = "";
            foreach (getTextLines($text) as $line) {
                if (preg_match("/^\#/", $line)) continue;
                if (preg_match("/^\s*(.*?)\s*:\s*(.*?)\s*$/", $line, $matches)) {
                    if (lcfirst($matches[1])==$blockStart && !is_empty($matches[2])) {
                        $blockKey = $matches[2];
                        $settings[$blockKey] = new HawkySet();
                    }
                    if (!empty($blockKey) && !empty($matches[1]) && !is_empty($matches[2])) {
                        $settings[$blockKey][$matches[1]] = $matches[2];
                    }
                }
            }
        }
        return $settings;
    }

    // Set settings in text
    function yamlset ($text, $blockStart, $blockKey, $settings) {
        $textNew = "";
        if (empty($blockStart)) {
            foreach (getTextLines($text) as $line) {
                if (preg_match("/^\s*(.*?)\s*:\s*(.*?)\s*$/", $line, $matches)) {
                    if (!empty($matches[1]) && isset($settings[$matches[1]])) {
                        $textNew .= "$matches[1]: ".$settings[$matches[1]]."\n";
                        unset($settings[$matches[1]]);
                        continue;
                    }
                }
                $textNew .= $line;
            }
            foreach ($settings as $key=>$value) {
                $textNew .= (mb_strpos($key, "/") ? $key : ucfirst($key)).": $value\n";
            }
        } else {
            $scan = false;
            $textStart = $textMiddle = $textEnd = "";
            foreach (getTextLines($text) as $line) {
                if (preg_match("/^\s*(.*?)\s*:\s*(.*?)\s*$/", $line, $matches)) {
                    if (lcfirst($matches[1])==$blockStart && !is_empty($matches[2])) {
                        $scan = lcfirst($matches[2])==lcfirst($blockKey);
                    }
                }
                if (!$scan && empty($textMiddle)) {
                    $textStart .= $line;
                } elseif ($scan) {
                    $textMiddle .= $line;
                } else {
                    $textEnd .= $line;
                }
            }
            $textSettings = "";
            foreach (getTextLines($textMiddle) as $line) {
                if (preg_match("/^\s*(.*?)\s*:\s*(.*?)\s*$/", $line, $matches)) {
                    if (!empty($matches[1]) && isset($settings[$matches[1]])) {
                        $textSettings .= "$matches[1]: ".$settings[$matches[1]]."\n";
                        unset($settings[$matches[1]]);
                        continue;
                    }
                    $textSettings .= $line;
                }
            }
            foreach ($settings as $key=>$value) {
                $textSettings .= (mb_strpos($key, "/") ? $key : ucfirst($key)).": $value\n";
            }
            if (!empty($textMiddle)) {
                $textMiddle = $textSettings;
                if (!empty($textEnd)) $textMiddle .= "\n";
            } else {
                if (!empty($textStart)) $textEnd .= "\n";
                $textEnd .= $textSettings;
            }
            $textNew = $textStart.$textMiddle.$textEnd;
        }
        return $textNew;
    }

    // Remove settings from text
    function yamlunset ($text, $blockStart, $blockKey) {
        $textNew = "";
        if (!empty($blockStart)) {
            $scan = false;
            $textStart = $textMiddle = $textEnd = "";
            foreach (getTextLines($text) as $line) {
                if (preg_match("/^\s*(.*?)\s*:\s*(.*?)\s*$/", $line, $matches)) {
                    if (lcfirst($matches[1])==$blockStart && !is_empty($matches[2])) {
                        $scan = lcfirst($matches[2])==lcfirst($blockKey);
                    }
                }
                if (!$scan && empty($textMiddle)) {
                    $textStart .= $line;
                } elseif ($scan) {
                    $textMiddle .= $line;
                } else {
                    $textEnd .= $line;
                }
            }
            $textNew = rtrim($textStart.$textEnd)."\n";
        }
        return $textNew;
    }

}

// vim: nospell
