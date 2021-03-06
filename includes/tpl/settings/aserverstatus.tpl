<table width="100%" border="0" cellspacing="2" cellpadding="0">
<tr>
<td width="20%"><strong>Server OS:</strong></td>
<td width="80%"> {$OS} </td>
</tr>
<tr>
<td><strong>Server Software:</strong></td>
<td> {$SOFTWARE} </td>
</tr>
<tr>
<td><strong>PHP Version:</strong></td>
<td> {$PHP_VERSION} (<a href="?page=status&sub=phpinfo">PHPInfo</a>)</td>
</tr>
<tr>
<td><strong>MySQL Version:</strong></td>
<td> {$MYSQL_VERSION} </td>
</tr>

<tr>
<td><strong>HTTP_HOST:</strong></td>
<td> {$SERVER} </td>
</tr>

<tr>
<td><strong>Free Disk Space:</strong></td>
<td> {$DISK_FREE_SPACE} GB / {$DISK_TOTAL_SPACE} GB</td>
</tr>

</table>
<table width="100%" border="0" cellspacing="2" cellpadding="0">
<tr>
<td align="center"><strong>HTTP</strong></td>
<td align="center"><strong>FTP</strong></td>
<td align="center"><strong>MySQL</strong></td>
<td align="center"><strong>POP3</strong></td>
<td align="center"><strong>SSH</strong></td>
</tr>
<tr>
<td align="center"> <img src="../includes/status.php?link={$SERVER}:80"></td>
<td align="center"> <img src="../includes/status.php?link={$SERVER}:21"></td>
<td align="center"> <img src="../includes/status.php?link={$SERVER}:3306"></td>
<td align="center"> <img src="../includes/status.php?link={$SERVER}:110"></td>
<td align="center"> <img src="../includes/status.php?link={$SERVER}:22"></td>
</tr>
</table>
