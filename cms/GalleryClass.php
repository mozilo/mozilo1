<?php
class GalleryClass {

    var $allowed_pics = array(".jpg", ".jpeg", ".jpe", ".gif", ".png", ".svg");
    var $GalleryArray = array();
    var $MenuArray = array();
    var $GalleriesArray = array();
    var $currentGallery = false;
    var $currentIndex = 1;
    var $currentGroup = 0;
    var $Cols = false;
    var $Rows = false;
    var $GalleryTemplate = false;

    function GalleryClass() {
        global $BASE_DIR, $GALLERIES_DIR_NAME;

        if(isset($_REQUEST['galtemplate']) and $_REQUEST['galtemplate'] == "true")
            $this->GalleryTemplate = true;

        # das ist nur ein array mit den Galerie Ordnernamen
        # ohne jegliche Prüfung
        $this->GalleriesArray = getDirAsArray($BASE_DIR.$GALLERIES_DIR_NAME,"dir");
#        if(isset($_REQUEST['gal']) and strlen($_REQUEST['gal']) > 0)
#            $this->currentGallery = $_REQUEST['gal'];
    }

    # Das muss nach $GalleryClass = new GalleryClass(); gemacht werden
    # $Galleries = "gallerie", array(gallerien) oder false für alle gallerien
    # $allowed_pics = array(erlaupte bilder endung mit oder ohne punkt)
    #                   false es wird Default liste genommen
    # $with_preview = wenn true es werden nur galerien und bilder ins array eingesetzt
    #               die auch Vorschaubilder haben
    #               wenn false es werden keine Vorschaubilder erzeugt
    # $with_description = wenn true es wird die Bildbeschreibung erzeugt
    #                   wenn false es wird keine Bildbeschreibung erzeugt
    function initial_Galleries($Galleries = false,$allowed_pics = false,
                                $with_preview = false,$with_description = false) {
        # wenn $allowed_pics ein array ist ansonsten wird Default $this->allowed_pics benutzt
        if($allowed_pics !== false and is_array($allowed_pics)) {
            $array = array();
            # alle ext im array in kleinschreibung wandeln und bei bedarf Punkt einsetzen
            foreach($allowed_pics as $z => $ext) {
                if(strlen($ext) < 2)
                    continue;
                $array[$z] = strtolower($ext);
                if(!strstr($ext,"."))
                    $array[$z] = ".".$ext;
            }
            if(count($array) > 0)
                $this->allowed_pics = $array;
        }
        if($Galleries !== false and !is_array($Galleries))
            $Galleries = array($Galleries);
        # Gallerien erstellen
        $this->GalleryArray = $this->make_DirGalleryArray($Galleries,$with_preview,$with_description);

        if(count($this->GalleryArray) < 1)
            return false;
        # wenn die currentGallery noch nicht gesetzt wurde einfach mal die erste setzen
        if($this->currentGallery === false)
            $this->currentGallery = key($this->GalleryArray);
        return true;
    }

    # Wenn man ein Menu braucht muss es vorher initaliesiert werden
    # $gallery = eine Galerie
    # $cols = Anzahl der Spalten
    # $rows = Anzahl der Zeilen
    # wenn $cols = false und $rows = false werden alle genommen
    function initial_GalleryMenu($gallery,$cols = false,$rows = false) {
        $menu_array = array();
        $image_group = count($this->GalleryArray[$gallery]);
        if($cols === false and $rows === false) {
            $this->Cols = $image_group;
            $this->Rows = 1;
        } elseif($cols !== false and $rows === false) {
            $this->Cols = $cols;
            $this->Rows = ceil($image_group / $cols);
        } elseif($cols === false and $rows !== false) {
            $this->Cols = ceil($image_group / $rows);
            $this->Rows = $rows;
        } elseif($cols !== false and $rows !== false) {
            $this->Cols = $cols;
            $this->Rows = $rows;
            if(($cols * $rows) < $image_group)
                $image_group = $cols * $rows;
        }
        $group = 0;
        $image_z = 1;
        foreach($this->GalleryArray[$gallery] as $image => $tmp) {
            $menu_array[$group][$image_z] = $image;
            if(($image_z > 0) and ($image_z % $image_group == 0))
                $group++;
            $image_z++;
        }
        $this->MenuArray[$gallery] = $menu_array;
        $this->currentGallery = $gallery;
        $this->set_currentGroupIndexFromRequest();
    }

###############################################################################
# Functionen die mit $gallery und $image Arbeiten
# Sind ab initial_Galleries() Verfügbar
###############################################################################

    function get_RequestGalery() {
        if(isset($_REQUEST['gal'])
                and strlen($_REQUEST['gal']) > 0
                and isset($this->GalleryArray[$_REQUEST['gal']]))
            return $_REQUEST['gal'];
        else
            return false;
    }

    # Sortiert die Galerien
    # $sort_type = ksort, krsort, rsort, sort, natcasesort, natsort, rnatcasesort, rnatsort
    #       oder number_(first/last)_($sort_type für Zahlen)_($sort_type für Text)
    #       z.B. $sort_type = number_first_sort_sort
    #           Es werden alle Galerien die mit einer Zahl beginnen als erstes dargestelt
    #           und mit sort Sortiert danach kommen alle die nicht mit einer Zahl beginen
    #           und werden mit sort Sortiert hier wird auch der $flag angewendet.
    #           Zuläsige $sort_type sind: none, rsort, sort, natcasesort, natsort,
    #                                    rnatcasesort und rnatsort
    #       none für keine Sortierung
    #       rnatcasesort und rnatsort ist in umgekehrter Reihenfolge
    # $flag = false(ist gleich Default), numeric, string und locale
    #       false = SORT_REGULAR
    #       numeric = SORT_NUMERIC
    #       string = SORT_STRING
    #       locale = SORT_LOCALE_STRING
    #       Mehr Info siehe PHP Beschreibung von sort()
    # Info die Sortierung ksort und krsort sind die schnelsten da direckt Sortiert wird
    function sort_Galleries($sort_type = false,$flag = false) {
        if($sort_type == "ksort" or $sort_type == "krsort") {
            $sort_type($this->GalleryArray,$this->helpSortFlags($flag));
        } elseif($sort_type == "natcasesort" or $sort_type == "natsort"
                or $sort_type == "rnatcasesort" or $sort_type == "rnatsort"
                or $sort_type == "rsort" or $sort_type == "sort") {
            $galleries = $this->get_GalleriesArray();
            if($sort_type == "natcasesort" or $sort_type == "natsort")
                $sort_type($galleries);
            elseif($sort_type == "rnatcasesort" or $sort_type == "rnatsort")
                $galleries = $this->$sort_type($galleries);
            else
                $sort_type($galleries,$this->helpSortFlags($flag));
            $this->helpMakeSortGalleries($galleries);
        } elseif(substr($sort_type,0,7) == "number_") {
            list($func,$order,$sortdigit,$sorttext) = explode("_",$sort_type);
            $this->helpSortGalleriesNumber($order,$sortdigit,$sorttext,$this->helpSortFlags($flag));
        }
    }

    # Sortiert die Bilder der Galerie(n)
    # $Galleries = TEXT, array(liste der Galerien) oder false für alle
    # $sort_type = siehe sort_Galleries()
    # $flag = siehe sort_Galleries()
    function sort_Images($Galleries = false,$sort_type = false,$flag = false) {
        if($Galleries !== false and is_array($Galleries))
            $Galleries = $Galleries;
        elseif($Galleries !== false and !is_array($Galleries))
            $Galleries = array($Galleries);
        else
            $Galleries = $this->get_GalleriesArray();
        foreach($Galleries as $gallery) {
            if(isset($this->GalleryArray[$gallery])) {
                if($sort_type == "ksort" or $sort_type == "krsort") {
                    $sort_type($this->GalleryArray[$gallery],$this->helpSortFlags($flag));
                } elseif($sort_type == "natcasesort" or $sort_type == "natsort"
                        or $sort_type == "rnatcasesort" or $sort_type == "rnatsort"
                        or $sort_type == "rsort" or $sort_type == "sort") {
                    $images = $this->get_GalleryImagesArray($gallery);
                    if($sort_type == "natcasesort" or $sort_type == "natsort")
                        $sort_type($images);
                    elseif($sort_type == "rnatcasesort" or $sort_type == "rnatsort")
                        $images = $this->$sort_type($images);
                    else
                        $sort_type($images,$this->helpSortFlags($flag));
                    $this->helpMakeSortImages($gallery,$images);

                } elseif(substr($sort_type,0,7) == "number_") {
                    list($func,$order,$sortdigit,$sorttext) = explode("_",$sort_type);
                    $this->helpSortImagesNumber($gallery,$order,$sortdigit,$sorttext,$this->helpSortFlags($flag));
                }
            }
        }
    }

    function get_GalleriesArray() {
        $return_array = array();
        foreach($this->GalleryArray as $gallery => $tmp) {
            $return_array[] = $gallery;
        }
        return $return_array;
    }

    function get_GalleryImagesArray($gallery) {
        $return_array = array();
        if(isset($this->GalleryArray[$gallery])
            and is_array($this->GalleryArray[$gallery])
            and count($this->GalleryArray[$gallery]) > 0) {
            foreach($this->GalleryArray[$gallery] as $images => $tmp) {
                $return_array[] = $images;
            }
        }
        return $return_array;
    }

    # $coded_as = html, url ,false = wie in texte.conf
    function get_ImageDescription($gallery,$image,$coded_as = false) {
        if(isset($this->GalleryArray[$gallery][$image]['description']) and false !== $this->GalleryArray[$gallery][$image]['description']) {
            $description = $this->GalleryArray[$gallery][$image]['description'];
            if($coded_as == "html") {
#                global $CHARSET;
                global $specialchars;
$description = $specialchars->rebuildSpecialChars($description,false,true);
#                $description = htmlentities($description,ENT_COMPAT,$CHARSET);
            } elseif($coded_as == "url")
                $description = rawurlencode($description);
            return $description;
        }
        return NULL;
    }
# $BASE_DIR.$GALLERIES_DIR_NAME."/".$gallery."/".$pic

    function get_ImagePath($gallery,$image,$preview = false) {
        global $BASE_DIR, $GALLERIES_DIR_NAME, $PREVIEW_DIR_NAME;
        if($preview === true)
            return $BASE_DIR.$GALLERIES_DIR_NAME."/".$gallery."/".$PREVIEW_DIR_NAME."/".$image;
        return $BASE_DIR.$GALLERIES_DIR_NAME."/".$gallery."/".$image;
    }

    function get_srcImage($gallery,$image,$preview = false) {
        global $URL_BASE, $GALLERIES_DIR_NAME, $PREVIEW_DIR_NAME;
        $gallery = rawurlencode($gallery);
        $image = rawurlencode($image);
#        $img = str_replace("%","%25",$URL_BASE.$GALLERIES_DIR_NAME."/".$img);
        if($preview === true)
            return $URL_BASE.$GALLERIES_DIR_NAME."/".$gallery."/".$PREVIEW_DIR_NAME."/".$image;
        return $URL_BASE.$GALLERIES_DIR_NAME."/".$gallery."/".$image;
    }

    function get_ImageType($image) {
        # ab denn letzen punkt ist die ext
        $type = substr($image,strrpos($image,"."));
        if(strlen($type) > 1)
            # kleingeschrieben zurück
            return strtolower($type);
        return false;
    }

    function set_currentGroupIndexFromRequest() {
        if(
                $this->get_RequestGalery() == $this->currentGallery
                and count($this->MenuArray[$this->currentGallery]) > 1
                and isset($_REQUEST['group'])
                and strlen($_REQUEST['group']) > 0
                and ctype_digit($_REQUEST['group'])
                and isset($this->MenuArray[$this->currentGallery][$_REQUEST['group']])
        )
            $this->currentGroup = $_REQUEST['group'];
        if(
                $this->get_RequestGalery() == $this->currentGallery
                and isset($_REQUEST['index'])
                and strlen($_REQUEST['index']) > 0
                and ctype_digit($_REQUEST['index'])
                and isset($this->MenuArray[$this->currentGallery][$this->currentGroup][$_REQUEST['index']])
        )
            $this->currentIndex = $_REQUEST['index'];
    }

###############################################################################
# Functionen die mit $group und $index Arbeiten
# Sind ab initial_GalleryMenu() Verfügbar
###############################################################################

    function get_HrefImage($preview,$index = false,$group = false) {
        global $URL_BASE, $GALLERIES_DIR_NAME, $PREVIEW_DIR_NAME;
        if($index === false)
            $index = $this->currentIndex;
        if($group === false)
            $group = $this->currentGroup;
        $image = $this->get_fromIndexGroupImage($index,$group);
        if($preview === true)
            return $URL_BASE.$GALLERIES_DIR_NAME."/".$this->currentGallery."/".$PREVIEW_DIR_NAME."/".$image;
        return $URL_BASE.$GALLERIES_DIR_NAME."/".$this->currentGallery."/".$image;
    }

    function get_ImageName($index = false,$group = false) {
        if($index === false)
            $index = $this->currentIndex;
        if($group === false)
            $group = $this->currentGroup;
        return $this->get_fromIndexGroupImage($index,$group);
    }

    function get_Description($coded_as = false,$index = false,$group = false) {
        if($index === false)
            $index = $this->currentIndex;
        if($group === false)
            $group = $this->currentGroup;

        $image = $this->MenuArray[$this->currentGallery][$group][$index];
$description = $this->GalleryArray[$this->currentGallery][$image]['description'];
        if(false !== $description) {
            $description = $this->GalleryArray[$this->currentGallery][$image]['description'];
            if($coded_as == "html") {
#                global $CHARSET;
                global $specialchars;
$description = $specialchars->rebuildSpecialChars($description,false,true);
#                $description = htmlentities($description,ENT_COMPAT,$CHARSET);
            } elseif($coded_as == "url")
                $description = rawurlencode($description);
            return $description;
        }

        return $description;
    }

    function get_GalIndexGroupUrl($array,$whith_group = false) {
        if(!is_array($array))
            $array = array($array,$this->currentGroup);
        $index = '&amp;index='.$array[0].'';
        $group = NULL;
        if($whith_group === true)
            $group = '&amp;group='.$array[1].'';
        return 'gal='.$this->currentGallery.$index.$group;
    }

    function get_firstIndex() {
#        if($group !== false)
            return array("1",$this->get_firstGroup());
#        return "1";
#$this->currentGallery
#$this->MenuArray[$Gallery]
    }

    function get_lastIndex() {
        $lastgroup = $this->get_lastGroup();
        end($this->MenuArray[$this->currentGallery][$lastgroup]);
        $lastimage = key($this->MenuArray[$this->currentGallery][$lastgroup]);
        return array($lastimage,$lastgroup);
    }

    function get_nextIndex($circular = true) {
        if(isset($this->MenuArray[$this->currentGallery][$this->currentGroup][($this->currentIndex + 1)]))
            return array(($this->currentIndex + 1),$this->currentGroup);
        elseif(isset($this->MenuArray[$this->currentGallery][($this->currentGroup + 1)][($this->currentIndex + 1)]))
            return array(($this->currentIndex + 1),($this->currentGroup + 1));
        elseif($circular === true)
            return $this->get_firstIndex(true);
#        else
            return array($this->currentIndex,$this->currentGroup);
#        if($group !== false)
#            return $return_array;
#        return $return_array[1];
    }

    function get_previousIndex($circular = true) {
        if(isset($this->MenuArray[$this->currentGallery][$this->currentGroup][($this->currentIndex - 1)]))
            return array(($this->currentIndex - 1),$this->currentGroup);
        elseif(isset($this->MenuArray[$this->currentGallery][($this->currentGroup - 1)][($this->currentIndex - 1)]))
            return array(($this->currentIndex - 1),($this->currentGroup - 1));
        elseif($circular === true)
            return $this->get_lastIndex(true);
#        else
            return array($this->currentIndex,$this->currentGroup);
#        if($group !== false)
#            return $return_array;
#        return $return_array[1];
    }

    function get_firstGroup() {
        return 0;
    }

    function get_lastGroup() {
        return (count($this->MenuArray[$this->currentGallery]) - 1);
    }

    function get_firstIndexFromGroup($group) {
        return key($this->MenuArray[$this->currentGallery][$group]);
    }

    function get_lastIndexFromGroup($group) {
        end($this->MenuArray[$this->currentGallery][$group]);
        return key($this->MenuArray[$this->currentGallery][$group]);
    }

    function get_nextGroup($circular = true) {
        if(isset($this->MenuArray[$this->currentGallery][($this->currentGroup + 1)])) {
            $group = ($this->currentGroup + 1);
            return array($this->get_firstIndexFromGroup($group),$group);
        } elseif($circular === true) {
            $group = $this->get_firstGroup();
            return array($this->get_firstIndexFromGroup($group),$group);
        }
        return array($this->currentIndex,$this->currentGroup);
    }

    function get_previousGroup($circular = true) {
        if(isset($this->MenuArray[$this->currentGallery][($this->currentGroup - 1)])) {
            $group = ($this->currentGroup - 1);
            return array($this->get_lastIndexFromGroup($group),$group);
        } elseif($circular === true) {
            $group = $this->get_lastGroup();
            return array($this->get_lastIndexFromGroup($group),$group);
        }
        return array($this->currentIndex,$this->currentGroup);
    }

    function get_currentIndexArray() {
        return array_keys($this->MenuArray[$this->currentGallery][$this->currentGroup]);
    }

    function get_fromIndexGroupImage($index,$group) {
        if(!isset($this->MenuArray[$this->currentGallery][$group][$index])) {
            $group = 0;
            $index = 1;
        }
        return $this->MenuArray[$this->currentGallery][$group][$index];
    }

    function create_ImgTag($alt,$css = false,$preview = false,$index = false,$group = false) {
        if($index === false)
            $index = $this->currentIndex;
        if($group === false)
            $group = $this->currentGroup;

#get_ImageDescription($gallery,$image,$coded_as = false) {
#get_srcImage($gallery,$image,$preview = false)
        global $URL_BASE;
        global $GALLERIES_DIR_NAME;
        $image = $this->get_fromIndexGroupImage($index,$group);
        $alttext = $alt;
        if($alt === false)
            $alttext = $this->get_ImageDescription($this->currentGallery,$image,"html");
        $csstext = NULL;
        if($css !== false)
            $csstext = ' class="'.$css.'"';
#        $text = $this->get_ImageDescription($this->currentGallery,$image);
#        $img = $this->currentGallery."/".$this->MenuArray[$this->currentGallery][$this->currentGroup][$this->currentIndex];
        $img = $this->get_srcImage($this->currentGallery,$image,$preview);

#        $img = str_replace("%","%25",$URL_BASE.$GALLERIES_DIR_NAME."/".$img);
#        $alt = 
        $img_tag = '<img src="'.$img.'" alt="'.$alttext.'"'.$csstext.' hspace="0" vspace="0" border="0" />';
        return $img_tag;
#<img align="middle" border="0">
    }

    function is_ImageActiv($index) {
        if($index == $this->currentIndex)
            return true;
        return false;
    }

    function get_CssActiv($index,$activtext = "active") {
        if($index == $this->currentIndex)
            return $activtext;
        return NULL;
    }

    function get_ColsRowsArray($group = false) {
        $cols_rows = array();
        if($group === false)
            $group = $this->currentGroup;

        $row = 0;
        foreach ($this->MenuArray[$this->currentGallery][$group] as $index => $img) {
            $cols_rows[$row][] = $index;
            if (($index > 0) && ($index % $this->Cols == 0))
                $row++;
        }
        $last_row_num = count($cols_rows) - 1;
        # wenn die letzten Zeile weniger cols hat sie mit false auffühlen
        if(count($cols_rows[$last_row_num]) != $this->Cols) {
            $cols = count($cols_rows[(count($cols_rows) - 1)]) - 1;
            $empty_cols = array_fill($cols, ($this->Cols - $cols - 1), false);
            $cols_rows[$last_row_num] = array_merge($cols_rows[$last_row_num],$empty_cols);
        }
        return $cols_rows;
    }

###############################################################################
# Hilfs Functionen
###############################################################################

    function rnatcasesort($Array) {
        natcasesort($Array);
        $Array = array_reverse($Array);
        return $Array;
    }

    function rnatsort($Array) {
        natsort($Array);
        $Array = array_reverse($Array);
        return $Array;
    }

    function helpSortFlags($flag) {
        $return_flag = SORT_REGULAR;
        if($flag == "numeric")
            $return_flag = SORT_NUMERIC;
        elseif($flag == "string")
            $return_flag = SORT_STRING;
        elseif($flag == "locale")
            $return_flag = SORT_LOCALE_STRING;
        return $return_flag;
    }

    function helpSortGalleriesNumber($order,$sortdigit,$sorttext,$flag) {
        if($sortdigit == "ksort" or $sortdigit == "krsort"
                or $sorttext == "ksort" or $sorttext == "krsort")
            return;
        $galarray_digit = array();
        $galarray_string = array();
        foreach($this->GalleryArray as $gallery => $tmp) {
            # ist erstes zeichen eine zahl
            if(ctype_digit($gallery[0])) {
                $galarray_digit[] = $gallery;
            # ist erstes zeichen keine zahl
            } else  {
                $galarray_string[] = $gallery;
            }
        }
        if($sortdigit == "natcasesort" or $sortdigit == "natsort")
            $sortdigit($galarray_digit);
        elseif($sortdigit == "rnatcasesort" or $sortdigit == "rnatsort")
            $galarray_digit = $this->$sortdigit($galarray_digit);
        elseif($sortdigit == "sort" or $sortdigit == "rsort")
            $sortdigit($galarray_digit,SORT_NUMERIC);

        if($sorttext == "natcasesort" or $sorttext == "natsort")
            $sorttext($galarray_string);
        elseif($sorttext == "rnatcasesort" or $sorttext == "rnatsort")
            $galarray_string = $this->$sorttext($galarray_string);
        elseif($sorttext == "sort" or $sorttext == "rsort")
            $sorttext($galarray_string,$flag);

        if($order == "last")
            $sortresult = array_merge($galarray_string, $galarray_digit);
        else
            $sortresult = array_merge($galarray_digit, $galarray_string);
        $this->helpMakeSortGalleries($sortresult);
    }

    function helpMakeSortGalleries($Galleries) {
        $tmp_array = array();
        foreach($Galleries as $gallery) {
            $tmp_array[$gallery] = $this->GalleryArray[$gallery];
        }
        $this->GalleryArray = $tmp_array;
        unset($tmp_array);
    }

    function helpMakeSortImages($Gallery,$Images) {
        $tmp_array = array();
        foreach($Images as $image) {
            $tmp_array[$image] = $this->GalleryArray[$Gallery][$image];
        }
        $this->GalleryArray[$Gallery] = $tmp_array;
        unset($tmp_array);
    }

    function helpSortImagesNumber($gallery,$order,$sortdigit,$sorttext,$flag) {
        if($sortdigit == "ksort" or $sortdigit == "krsort"
                or $sorttext == "ksort" or $sorttext == "krsort")
            return;
        $image_digit = array();
        $image_string = array();
        foreach($this->GalleryArray[$gallery] as $image => $tmp) {
            # ist erstes zeichen eine zahl
            if(ctype_digit($image[0])) {
                $image_digit[] = $image;
            # ist erstes zeichen keine zahl
            } else  {
                $image_string[] = $image;
            }
        }

        if($sortdigit == "natcasesort" or $sortdigit == "natsort")
            $sortdigit($image_digit);
        elseif($sortdigit == "rnatcasesort" or $sortdigit == "rnatsort")
            $image_digit = $this->$sortdigit($image_digit);
        elseif($sortdigit == "sort" or $sortdigit == "rsort")
            $sortdigit($image_digit,SORT_NUMERIC);

        if($sorttext == "natcasesort" or $sorttext == "natsort")
            $sorttext($image_string);
        elseif($sorttext == "rnatcasesort" or $sorttext == "rnatsort")
            $image_string = $this->$sorttext($image_string);
        elseif($sorttext == "sort" or $sorttext == "rsort")
            $sorttext($image_string,$flag);

        if($order == "last")
            $sortresult = array_merge($image_string, $image_digit);
        else
            $sortresult = array_merge($image_digit, $image_string);

        $this->helpMakeSortImages($gallery,$sortresult);

    }

###############################################################################
# Ab hier solten die functionen nur von der function GalleryClass() verwendet werden
###############################################################################

    function make_DirGalleryArray($Galleries,$with_preview,$with_description) {
#!!!!!!!!! hier muss noch nee prüfung rein das wenn galerie keine bilder hat sie erst garnich erscheint

        global $BASE_DIR;
        global $GALLERIES_DIR_NAME;
        global $PREVIEW_DIR_NAME;
        $GALERIE_DIR = $BASE_DIR.$GALLERIES_DIR_NAME."/";
#echo "$GALERIE_DIR<br>\n";
        $return_array = array();
        if($Galleries !== false and is_array($Galleries)) {
            $galleries_array = $Galleries;
        } else
            $galleries_array = getDirAsArray($GALERIE_DIR,"dir");

        foreach($galleries_array as $gallery) {
#echo $GALERIE_DIR.$gallery."<br>\n";
            $description = array();
            $gallery_images = getDirAsArray($GALERIE_DIR.$gallery,$this->allowed_pics);
            # Galerie hat keine Bilder also nicht erstellen
            if(count($gallery_images) < 1)
                continue;
            # Bildbeschreibung soll benutzt werden und texte.conf gibts also erzeugen
            if($with_description === true
                    and count($gallery_images) > 0
                    and file_exists($GALERIE_DIR.$gallery."/"."texte.conf")) {
                if(false !== ($tmp_description = file($GALERIE_DIR.$gallery."/"."texte.conf"))) {
                    foreach($tmp_description as $zeile) {
                        if(strpos($zeile," = ") < 3)
                            continue;
                        $zeile = trim($zeile);
                        $image = substr($zeile,0,strpos($zeile," = "));
                        $descript = substr($zeile,(strpos($zeile," = ") + 3));
                        $description[$image] = $descript;
                    }
                }
                unset($tmp_description);
            }
            foreach($gallery_images as $image) {
                # Bild hat kein Vorschaubild, Vorschaubilder sollen aber benutzt werden
                # dann nicht ins array
                if($with_preview === true
                        and !file_exists($GALERIE_DIR.$gallery."/".$PREVIEW_DIR_NAME."/".$image))
                    continue;
#echo "$image<br>\n";
                $return_array[$gallery][$image]['preview'] = false;
                # Vorschaubilder sollen benutzt werden und Vorschaubild gibt es
                if($with_preview === true)
                    $return_array[$gallery][$image]['preview'] = true;
                $return_array[$gallery][$image]['description'] = false;
                # Bildbeschreibung soll benutzt werden wenn vorhanden ins array
                if($with_description === true and isset($description[$image]))
                    $return_array[$gallery][$image]['description'] = $description[$image];
            }
            # hat Galerie keine Bilder dann löschen
            if(isset($return_array[$gallery]) and count($return_array[$gallery]) < 1)
                unset($return_array[$gallery]);
        }
        return $return_array;
    }
/*
            if(in_array($this->get_ImageType($image),$this->allowed_pics))

    $size    = getimagesize($dir_origin.$pic);
    $mime    = $size['mime'];
    $width  = $size[0];
    $height = $size[1];

[GALLERY]
    [BILDNAME] array(
                    [preview] false oder true
                    [description] false oder TEXT
                    )

        global $EXT_LINK;
        global $CONTENT_FILES_DIR_NAME;
        $page_a = array();
        $page_sort = array();
        $currentdir = getDirAsArray($dir,"file");
        foreach($currentdir as $file) {
            if(substr($file, -4) == $EXT_LINK) {
                $target = "-_blank-";
                if(strpos($file,"-_self-") > 1)
                    $target = "-_self-";
                $tmp = explode($target,$file);
                $key = substr($tmp[0],3);
                $page_a[$key]["_name-"] = $key;
                $page_a[$key]["_orgname-"] = $page_a[$key]["_name-"];
                $page_a[$key]["_pos-"] = substr($file,0,2);
                $page_a[$key]["_type-"] = $EXT_LINK;
                $page_a[$key]["_link-"] = str_replace($this->link_search,$this->link_replace,substr($tmp[1],0,strlen($tmp[1])-4));
                $page_a[$key]["_target-"] = str_replace("-","",$target);
            } else {
                $key = substr($file,3,strlen($file)-7);
                $page_a[$key]["_name-"] = $key;
                $page_a[$key]["_orgname-"] = $page_a[$key]["_name-"];
                $page_a[$key]["_pos-"] = substr($file,0,2);
                $page_a[$key]["_type-"] = substr($file,-4);
                $page_a[$key]["_time-"] = filemtime($dir."/".$file);
            }
        }
        return $page_a;
    }*/

}
?>