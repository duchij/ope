<?php /* Smarty version 2.6.28, created on 2015-04-04 13:46:25
         compiled from search.tpl */ ?>
﻿<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN""http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
	<link rel="stylesheet" type="text/css" href="css/layout.css">

	<meta charset="UTF-8">
	<title>Ref Zmluvy - Končiace zmluvy</title>	

</head>
<body>

<div id="wrapper">
		<div id="header">
			<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "header.tpl", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
			
		</div>

<div id="content">

		<div id="content-left">
			<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "main_menu.tpl", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
		</div>
		
	<div id="content-main" style="width:750px;">
			<h3><?php echo $this->_tpl_vars['refz']['message']; ?>
</h3>

			
			<form name="form1" method='post' action="app.php">
			<input type="hidden" name="class" value="refz">
			
			<hr>
			<h2><?php echo $this->_tpl_vars['search_result']; ?>
</h2>
			<hr>
			<table style="width:100%;">
				<tr>
					<td width="50px"><strong>Registračné číslo</strong></td>
					<td><strong>Predmet</strong></td>
					<td width="50px"><strong>Uzavretie</strong></td>
					<td width="50px"><strong>Dĺžka splatnosti</strong></td>
					<td width="50px"><strong>Platnosť<br></strong></td>
					<td width="50px"><strong>Zverejnené CRZ</strong></td>
					<td width="50px" align="right"><strong>Príloha</strong></td>
					
			<?php $_from = $this->_tpl_vars['result']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['i'] => $this->_tpl_vars['row']):
?>
				<tr>
					<td valign="top"><a href="http://apm:81/refzmluvy/gener/zmluvyview.php?z_ID=<?php echo $this->_tpl_vars['row']['z_ID']; ?>
"><?php echo $this->_tpl_vars['row']['reg_cislo']; ?>
</a></td>
					<td valign="top" style="background-color: silver;"><?php echo $this->_tpl_vars['row']['predmet']; ?>
</td>
					<td valign="top"><?php echo $this->_tpl_vars['row']['uzavretie']; ?>
</td>
					<td valign="top" style="background-color: silver;"><?php echo $this->_tpl_vars['row']['dlzka_splatnosti']; ?>
</td>
					<td valign="top" style="color:red;"><?php echo $this->_tpl_vars['row']['platnost']; ?>
</td>
					<td valign="top" style="background-color: silver;"><?php echo $this->_tpl_vars['row']['zverejn_CRZ']; ?>
</td>
					<td valign="top" align="right"><a href="http://apm:81/refzmluvy/prilohy/<?php echo $this->_tpl_vars['row']['priloha_nazov']; ?>
" target="_blank"><?php echo $this->_tpl_vars['row']['priloha_nazov']; ?>
</a></td>
					
				</tr>
            <?php endforeach; endif; unset($_from); ?>
			</table>
		</form>
		</div>
	
	<!--<div id="content-right">
			<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "tags.tpl", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>
		
		</div>-->
	</div>
	<div id="footer"><?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "footer.tpl", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?></div>
	<div id="bottom"></div>
	
</div>
<?php echo '
<!-- <script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/app.js"></script> -->
'; ?>

</body>

</html>