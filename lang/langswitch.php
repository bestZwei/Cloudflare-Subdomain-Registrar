<?php
session_start();
if($_GET['language'] == true && $_GET['url'] == true && $_GET['language'] == "en" or $_GET['language'] == "zh" or $_GET['language'] == "ja"){
	    $_SESSION["language"] = $_GET['language'];	
	    header("Location: ../".$_GET['url']);
    }else{
	    echo "success=false";
    }