<?php /* Smarty version 2.6.12, created on 2010-06-02 04:41:23
         compiled from c:/Files/webopac/themes/blue/AdminLogin.html */ ?>
<form name="form1" method="post" action="<?php echo $this->_tpl_vars['action']; ?>
">
  <table width="50%" align="center" cellpadding="5">
    <tr>
      <td><h3 align="center">Admin Login </h3></td>
    </tr>
    <tr>
      <td valign="top"><table width="100%" border="0" align="center" class="box1">
        <tr>
          <td colspan="2" bgcolor="#CCCCCC"><strong>Login </strong></td>
        </tr>
        <tr>
          <td width="38%">&nbsp;</td>
          <td width="62%">&nbsp;</td>
        </tr>
        <tr>
          <td align="right">Password</td>
          <td><input name="password" type="password" id="password" size="25" maxlength="15" /></td>
        </tr>
        <tr>
          <td>&nbsp;</td>
          <td><input type="submit" name="Submit" value="Login" /></td>
        </tr>
      </table>        
        <p><span class="error"><?php echo $this->_tpl_vars['error']; ?>
</span></p>
      </td>
    </tr>
  </table>
  <?php echo $this->_tpl_vars['hidden']; ?>

</form>