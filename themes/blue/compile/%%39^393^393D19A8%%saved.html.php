<?php /* Smarty version 2.6.12, created on 2011-03-07 06:58:40
         compiled from c:/Files/webopac/themes/blue/saved.html */ ?>
<?php echo '
<script>
function printpage(curhost,hostname){
	//open new window
	var chk = document.getElementById(\'ListViewForm\').checkitem;
	var ids = [];
	for (i=0;i<chk.length;i++ ){
		var obj = chk[i];
		if(obj.checked){
			ids.push(obj.value);
		}
	}
	
	window.open("printpage.php?curhost="+curhost+"&hostname="+hostname+"&ids="+ids,"","height=600,width=700,top=10,left=10");
}
function emailpage(curhost,hostname){
	var chk = document.getElementById(\'ListViewForm\').checkitem;
	var ids = [];
	for (i=0;i<chk.length;i++ ){
		var obj = chk[i];
		if(obj.checked){
			ids.push(obj.value);
		}
	}
	
	var email = document.getElementById(\'email\').value;
	if (email == \'\') { alert (\'Please enter a valid email address\');return }
	window.open("email.php?curhost="+curhost+"&hostname="+hostname+"&ids="+ids,"mailsend","height=350,width=350,top=10,left=10");
}
function selecthost(){
	var selecthost = document.getElementById(\'selecthost\').value.split(\'_\');
	document.location = \'index.php?m=search&s=saved&curhost=\'+selecthost[0]+\'&hostname=\'+selecthost[1];
	
}
</script>





'; ?>

<?php echo $this->_tpl_vars['saved']; ?>