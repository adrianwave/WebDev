<?php
    include("config.php");
    include("classes/DomDocumentParser.php");

    $alreadyCrawled = array();
    $crawling = array();

    function insertLink($url, $title, $description, $keywords) {
        global $con;
        
        $query = $con->prepare("INSERT into sites (url, title, description, keywords)
                                VALUES(:url, :title, :description, :keywords)");
        $query->bindParam(":url", $url);
        $query->bindParam(":title", $title);
        $query->bindParam(":description", $description);
        $query->bindParam(":keywords", $keywords);

        return $query->execute();
    }

    function linkExists($url) {
        global $con;
        
        $query = $con->prepare("SELECT * FROM sites WHERE url = :url");
        $query->bindParam(":url", $url);
        $query->execute();

        return $query->rowCount() != 0;
    }

    function createLink($src, $url) {
        $scheme = parse_url($url)['scheme']; // http
        $host = parse_url($url)['host']; // www.website.com
        if(substr($src, 0, 2) == "//") {
            $src = parse_url($url)['scheme'] . ":" . $src;
        } else if(substr($src, 0, 1) == "/") {
            $src = $scheme . "://" . $host . $src;
        } else if(substr($src, 0, 2 == "./")) {
            $src = $scheme . "://" . $host . dirname(parse_url($url))['path'] . substr($src, 1);
        } else if(substr($src, 0, 3 == "../")) {
            $src = $scheme . "://" . $host . "/" . $src;
        } else if(substr($src, 0, 5) !=  "https" && substr($src, 0, 4) != "http") {
            $src = $scheme . "://" . $host . "/" . $src;
        }

        return $src;
    }

    function getDetails($url) {
        $parser = new DomDocumentParser($url);
        $titleArray = $parser->getTitleTags();

        if(sizeof($titleArray) == 0 || $titleArray->item(0) == NULl) {
            return;
        }

        $title = $titleArray->item(0)->nodeValue;
        $title = str_replace("\n", "", $title);
        
        if($title == "") {
            return;
        }

        $description = "";
        $keywords = "";

        $metasArray = $parser->getMetaTags();

        foreach($metasArray as $meta) {

            if($meta->getAttribute("name") == "description") {
                $description = $meta->getAttribute("content");
            }

            if($meta->getAttribute("name") == "keywords") {
                $keywords = $meta->getAttribute("content");
            }
        }

        $description = str_replace("\n", "", $description);
        $keywords = str_replace("\n", "", $keywords);

        if(linkExists($url)) {
            echo "ERROR: ALREADY EXISTS ($url)<br />";
        } else if(insertLink($url, $title, $description, $keywords)) {
            echo "SUCCESS: ($url)<br />";
        } else {
            echo "ERROR: FAILED TO INSERT ($url)<br />";
        }
    }

    function followLinks($url) {

        global $alreadyCrawled;
        global $crawling;

        $parser = new DomDocumentParser($url);
        $linkList = $parser->getLinks();

        foreach($linkList as $link) {
            $href = $link->getAttribute("href");

            if(strpos($href, "#") !== false) {
                continue;
            } else if(substr($href, 0, 11) == "javascript:") {
                continue;
            }


            $href = createLink($href, $url);
            
            if(!in_array($href, $alreadyCrawled)) {
                $alreadyCrawled[] = $href;
                $crawling[] = $href;
                getDetails($href);
            } else return;
        }

        array_shift($crawling);

        foreach($crawling as $site) {
            followLinks($site);
        }
    }
    
    $startUrl = "http://www.bbc.com";
    followLinks($startUrl);
?>