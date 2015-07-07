<?php /* Smarty version 2.6.28, created on 2015-04-04 13:44:44
         compiled from main_menu.tpl */ ?>
<h2>Končí do</h2>
platí od dnešného dňa plus daný mesiac.....
<hr>

		<?php if ($this->_tpl_vars['admin']): ?>
<!-- 			<li><a href="app.php?addcon=1">Kongresy</a></li> -->
<!-- 			<li><a href="app.php?fform_fnc=1">FORM Designer</a></li> -->
			<hr>
		<?php endif; ?>
			<form method="post" action="app.php" id="myform">
				<input type="hidden" name="class" value="refz">
				<ul>
					<li><a href="app.php?m=1" target="_self">Jedného mesiaca</a></li>
					<li><a href="app.php?m=2" target="_self">Dvoch mesiacov</a></li>
					<li><a href="app.php?m=3" target="_self">Troch mesiacov</a></li> 
					<li><a href="http://apm:81/refzmluvy/gener" target="_self">Návrat do refZmluvy</a></li>
				</ul>