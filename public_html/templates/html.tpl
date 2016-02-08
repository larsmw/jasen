<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" 
	   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>[@title]</title>
<meta charset="UTF-8" />
  [@header]
<script>
function showStats() {
  item = "ajax";
  if (window.XMLHttpRequest) {
    // code for IE7+, Firefox, Chrome, Opera, Safari
    xmlhttp = new XMLHttpRequest();
  } else {
    // code for IE6, IE5
    xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
  }
  xmlhttp.onreadystatechange = function() {
    if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
      document.getElementById(item).innerHTML = xmlhttp.responseText;
    }
  };
  xmlhttp.open("GET","ajax/crawler/stats/list",true);
  xmlhttp.send();
}
</script>
</head>
<body onload="showStats();">
  <div id="header">
    <h2><a href="/">Linkhub</a></h2>
    <h3>Simple PHP Template Engine</h3>
  </div>
  <div id="content">
    [@messages]
    [@content]
  </div>
  <div id="sidebar">
    <div id="menu">
      <h2>Navigation</h2>
      <ul>
	<li><a href="/html/user">User profile</a> - example of a user profile page</li>
      </ul>
    </div>
    <div id="ajax"></div>
    [@sidebar]
  </div>
  <div id="footer">
    [@footer]
  </div>
  [@post_files]
</body>
</html>
