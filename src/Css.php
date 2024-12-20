<?php
////////////////////////////////////////////////////////////////////////////////
//BOCA Online Contest Administrator
//    Copyright (C) 2003-2012 by BOCA Development Team (bocasystem@gmail.com)
//
//    This program is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//    You should have received a copy of the GNU General Public License
//    along with this program.  If not, see <http://www.gnu.org/licenses/>.
////////////////////////////////////////////////////////////////////////////////
// Last modified 05/aug/2012 by cassio@ime.usp.br
$corfundo = "#e0e0d0";
$corfrente = "#000000";
$corfundo2 = "#dfdfdf";
$cormenu = "#dfdfdf";
$cordestaque = "#ffff00";
?>
div#popupnew {
position:absolute;
left:50%;
top:17%;
margin-left:-202px;
font-family:'Raleway',sans-serif
}
div#normal {
width:100%;
height:100%;
opacity:.95;
top:0;
left:0;
display:none;
position:fixed;
background-color:#313131;
overflow:auto
}
DIV.menu {background-color:<?php echo $corfundo?>; layer-background-color:<?php echo $corfundo?>}
DIV.menudown {background-color:<?php echo $cormenu?>; border-bottom:1px solid white; border-right:1px solid white;border-top:2px solid #555555;border-left:1px solid #555555}
DIV.fname {background-color:<?php echo $corfundo2?>; layer-background-color:<?php echo $corfundo2?>; position:absolute; visibility:hidden; border:0; left:0px; top:0px; height:19px; z-index:100;}
DIV.dir {background-color:<?php echo $corfundo?>; layer-background-color:<?php echo $corfundo?>; position:absolute; visibility:hidden; border:0; left:0px; top:0px; height:19px;z-index:100; }
A {font-family:"Courier New", Courier, mono; font-size:12pt; color:<?php echo $corfrente?>} 
A.header {font-family:Verdana, Arial, Helvetica, sans-serif; font-size:12pt} 
A.menu {font-family:Verdana, Arial, Helvetica, sans-serif; text-decoration:none; font-size:12pt; border: 1px solid transparent} 
A.menu:hover {background-color:<?php echo $cormenu?>; border-bottom:1px solid #555555; border-right:1px solid #555555;border-top:1px solid white;border-left:1px solid white} 
A.current-page {border-bottom: 4px solid;}
A.current-page:hover {border-bottom: 4px solid;}
A.user {font-family:Verdana, Arial, Helvetica, sans-serif; font-size:12pt} 
A.user:hover {font-weight: bolder} 
A.disabled {font-family:Verdana, Arial, Helvetica, sans-serif; font-size:12pt; text-decoration:none; color:#BFBFBF} 
A.form {font-family:Verdana, Arial, Helvetica, sans-serif; font-size:12pt; background-color:<?php echo $cormenu?>} 
BODY {background-color:<?php echo $corfundo?>; font-family:"Courier new", monospace; font-size:12pt; color:<?php echo $corfrente?>} 
BODY.cline {background-color:#000000; color:#FFFFFF} 
TABLE { font-family:"Courier New", Courier, mono; font-size:12pt } 
TABLE.form { font-family:Verdana, Arial, Helvetica, sans-serif; font-size:12pt } 
FORM { font-size:12pt;} 
FORM.alt { font-size:12pt; margin-top: 5px } 
FORM.fname { font-size:12pt; margin: 0px} 
INPUT.fname { font-family:"Courier New", Courier, mono;font-size:12pt; border:0; background-color:<?php echo $corfundo2?> }
FORM.dir { font-size:12pt; margin: 0px} 
INPUT.dir { font-family:"Courier New", Courier, mono;font-size:12pt; border:0; background-color:<?php echo $corfundo?> }
<?php if( strstr(getenv("HTTP_USER_AGENT"), "MSIE")) { ?>
input.checkbox { border:none }
<?php } else { ?>
input.checkbox { }
<?php } ?>
INPUT { font-size:12pt; border:1px solid #555555}
INPUT.cline { background-color:#000000; font-family:"Courier new", monospace; font-size:12pt; color:#FFFFFF; border:0} 
TEXTAREA { border:1px solid #555555 }
TEXTAREA.edit { font-family:"Courier New", Courier, mono;font-size:10pt; background-color:#EFEFEF } 
SELECT { font-size:12pt;}
p.link a:hover {background-color: #2B2E21;;color:#fff;}
p.link a:link span{display: none;}
p.link a:visited span{display: none;}
p.link a:hover span {
  position: absolute;
  margin:15px 0px 0px 20px;
  background-color: beige;
  max-width:220;
  padding: 2px 10px 2px 10px;
  border: 1px solid #C0C0C0;
  font: normal 10px/12px verdana;
  color: #000;
  text-align:left;
  display: block;
}

div.grd_headTblCont {
  border: none;
  margin: 0;
  background-color: <?php echo $corfundo?>;
}

div.grd_headTblCont table,
div.grd_tblCont table {
  border-collapse: separate;
  border: black;
}

div.grd_headTblCont table td {
  height: inherit;
  background-color: <?php echo $corfundo?>;
  border: 1px solid black !important;
  padding: 0;
}

div.grd_Cont {
  box-shadow: none;
  background-color: inherit;
  border: 1px solid;
}

div.grd_Cont .flt {
  height: auto;
  font-family: system-ui;
  font-size: 12pt;
  border: 0;
  border-radius: 0;
}

div.grd_tblCont table td {
  border-bottom: 1px solid !important;
  text-wrap: wrap;
  padding: 0;
  word-wrap: break-word;
  overflow: auto;
}

div.grd_tblCont {
  background: transparent;
  overflow: auto;
}

.inf {
  background-color: <?php echo $corfundo?>;
  border: 0;
  border-radius: 0;
}

select.pgSlc {
  height: auto;
  margin: inherit;
  border: initial;
  vertical-align: inherit;
}

select.pgSlc:focus {
  border-color: inherit;
  outline: inherit;
  box-shadow: inherit;
}

span .pgInp,
span .reset {
  cursor: pointer;
}

span.rspgSpan {
  display: inline-block;
  padding: .5em;
  vertical-align: top;
}

select.rspg {
  height: auto;
  margin-top: 5px;
  border: initial;
  vertical-align: top;
}

input.error {
  color: red;
  border: solid 1px red;
}

#error-message {
  color: red;
  font-weight: bold;
  text-align: center;
}

.highlight {background-color: <?php echo $cordestaque?>;}

[class^="bi-"]::before,
[class*=" bi-"]::before {
  display: inline-block;
  font-family: bootstrap-icons !important;
  font-style: normal;
  font-weight: normal !important;
  font-variant: normal;
  text-transform: none;
  line-height: 2;
  vertical-align: text-top;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

.bi-eye-fill::before { content: url('images/password/eye-fill.svg'); }
.bi-eye-slash-fill::before { content: url(images/password/eye-slash-fill.svg); }
.bi-eye-slash::before { content: url(images/password/eye-slash.svg); }
.bi-eye::before { content: url('images/password/eye.svg'); }

i.bi-eye-slash {
  margin-left: -30px;
  cursor: pointer;
}

.solved {
  background: #b0b0a0;
  opacity: 0.5;
}

.tags {
  padding: 5px 0px;
}

/* Base styles for all tags */
.tag {
  display: inline-block;
  margin: 2px;
  padding: 0px 5px;
  font-weight: normal;
  font-size: smaller;
  border-radius: 8px;
}

/* Group container styles */
.tag-group {
  margin: 2px;
  display: block;
}

.tag-group[data-group='stat'] {
  margin: 0px;
  display: block;
}

/* Group heading styles */
.tag-group h4 {
  margin: 0px;
  font-weight: normal;
  color: #333;
  text-transform: capitalize;
}

/* Tag styles based on the data-key attribute */
.tag[data-key='group'] {
  border: 1px solid #057471; /* Green */
  background-color: #e5efe8;
  color: #057471;
}

.tag[data-key='level'] {
  border: 1px solid #024b84; /* Blue */
  background-color: #dee8ef;
  color: #024b84;
}

.tag[data-key='domain'] {
  border: 1px solid #c37400; /* Brown */
  background-color: #ece7e1;
  color: #875000;
}

.tag[data-key='lang'] {
  border: 1px solid #6f0082; /* Purple */
  background-color: #eae5eb;
  color: #6f0082;
}

.tag[data-key='stat'] {
  margin: 0 2px;
  font-size: smaller;
  color: #000000; /* Black */
}

.tooltip {
  cursor: help;
  color: #007BFF;
  margin-left: -5px;
}
