<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" 
	   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<title>[@title]</title>
<meta charset="UTF-8" />
  [@header]
</head>
<body>
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
	<li><a href="/user">User profile</a> - example of a user profile page</li>
      </ul>
    </div>
    [@sidebar]
  </div>
  <div id="footer">
    [@footer]
  </div>
  [@post_files]
</body>
</html>
