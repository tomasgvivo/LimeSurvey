<?php
/*
	#############################################################
	# >>> PHP Surveyor  										#
	#############################################################
	# > Author:  Jason Cleeland									#
	# > E-mail:  jason@cleeland.org								#
	# > Mail:    Box 99, Trades Hall, 54 Victoria St,			#
	# >          CARLTON SOUTH 3053, AUSTRALIA
 	# > Date: 	 20 February 2003								#
	#															#
	# This set of scripts allows you to develop, publish and	#
	# perform data-entry on surveys.							#
	#############################################################
	#															#
	#	Copyright (C) 2003  Jason Cleeland						#
	#															#
	# This program is free software; you can redistribute 		#
	# it and/or modify it under the terms of the GNU General 	#
	# Public License as published by the Free Software 			#
	# Foundation; either version 2 of the License, or (at your 	#
	# option) any later version.								#
	#															#
	# This program is distributed in the hope that it will be 	#
	# useful, but WITHOUT ANY WARRANTY; without even the 		#
	# implied warranty of MERCHANTABILITY or FITNESS FOR A 		#
	# PARTICULAR PURPOSE.  See the GNU General Public License 	#
	# for more details.											#
	#															#
	# You should have received a copy of the GNU General 		#
	# Public License along with this program; if not, write to 	#
	# the Free Software Foundation, Inc., 59 Temple Place - 	#
	# Suite 330, Boston, MA  02111-1307, USA.					#
	#############################################################	
*/

if (!$_GET['ok'])
	{
	//CHECK TO MAKE SURE ALL QUESTION TYPES THAT REQUIRE ANSWERS HAVE ACTUALLY GOT ANSWERS
	//THESE QUESTION TYPES ARE:
	//	# "L" -> LIST
	//  # "O" -> LIST WITH COMMENT
	//  # "M" -> MULTIPLE OPTIONS
	//	# "P" -> MULTIPLE OPTIONS WITH COMMENTS
	//	# "A", "B", "C", "E", "F" -> Various Array Types
	//  # "R" -> RANKING
	$chkquery = "SELECT qid, question FROM questions WHERE sid={$_GET['sid']} AND type IN ('L', 'O', 'M', 'P', 'A', 'B', 'C', 'E', 'F', 'R')";
	$chkresult = mysql_query($chkquery) or die ("Couldn't get list of questions<br />$chkquery<br />".mysql_error());
	while ($chkrow = mysql_fetch_array($chkresult))
		{
		$chaquery = "SELECT * FROM answers WHERE qid = {$chkrow['qid']} ORDER BY sortorder, answer";
		$charesult=mysql_query($chaquery);
		$chacount=mysql_num_rows($charesult);
		if (!$chacount > 0) 
			{
			$failedcheck[]=array($chkrow['qid'], $chkrow['question'], ": "._AC_MULTI_NOANSWER);
			}
		}
		
	//NOW CHECK THAT ALL QUESTIONS HAVE A 'QUESTION TYPE' FIELD
	$chkquery = "SELECT qid, question FROM questions WHERE sid={$_GET['sid']} AND type = ''";
	$chkresult = mysql_query($chkquery) or die ("Couldn't check questions for missing types<br />$chkquery<br />".mysql_error());
	while ($chkrow = mysql_fetch_array($chkresult))
		{
		$failedcheck[]=array($chkrow['qid'], $chkrow['question'], ": "._AC_NOTYPE);
		}
	
	//CHECK THAT ALL CONDITIONS SET ARE FOR QUESTIONS THAT PRECEED THE QUESTION CONDITION
	//A: Make an array of all the qids in order of appearance
	$qorderquery="SELECT * FROM questions, groups WHERE questions.gid=groups.gid AND questions.sid={$_GET['sid']} ORDER BY group_name, questions.title";
	$qorderresult=mysql_query($qorderquery) or die("Couldn't generate a list of questions in order<br />$qorderquery<br />".mysql_error());
	$qordercount=mysql_num_rows($qorderresult);
	$c=0;
	while ($qorderrow=mysql_fetch_array($qorderresult)) 
		{
		$qidorder[]=array($c, $qorderrow['qid']);
		$c++;
		}
	//1: Get each condition's question id
	$conquery="SELECT conditions.qid, cqid, questions.question FROM conditions, questions, groups WHERE conditions.qid=questions.qid AND questions.gid=groups.gid ORDER BY qid";
	$conresult=mysql_query($conquery) or die("Couldn't check conditions for relative consistency<br />$conquery<br />".mysql_error());
	//2: Check each conditions cqid that it occurs later than the cqid
	while ($conrow=mysql_fetch_array($conresult))
		{
		$cqidfound=0;
		$qidfound=0;
		$b=0;
		while ($b<$qordercount)
			{
			if ($conrow['cqid'] == $qidorder[$b][1])
				{
				$cqidfound = 1;
				$b=$qordercount;
				}
			if ($conrow['qid'] == $qidorder[$b][1])
				{
				$qidfound = 1;
				$b=$qordercount;
				}
			if ($qidfound == 1)
				{
				$failedcheck[]=array($conrow['qid'], $conrow['question'], ": "._AC_CON_OUTOFORDER);
				}
			$b++;
			}
		}
	//IF ANY OF THE CHECKS FAILED, PRESENT THIS SCREEN
	if ($failedcheck)
		{
		echo "<br />\n<table width='350' align='center' style='border: 1px solid #555555' cellpadding='1' cellspacing='0'>\n";
		echo "\t\t\t\t<tr bgcolor='#555555'><td height='4'><font size='1' face='verdana' color='white'><b>"._ACTIVATE." ($sid)</b></td></tr>\n";
		echo "\t<tr>\n";
		echo "\t\t<td align='center' bgcolor='pink'>\n";
		echo "\t\t\t<font color='red'>$setfont<b>"._ERROR."</b><br />\n";
		echo "\t\t\t"._AC_FAIL."</font></font>\n";
		echo "\t\t</td>\n";
		echo "\t</tr>\n";
		echo "\t<tr>\n";
		echo "\t\t<td>\n";
		echo "\t\t\t$setfont<b>"._AC_PROBS."</b><br />\n";
		echo "\t\t\t<ul>\n";
		foreach ($failedcheck as $fc)
			{
			echo "\t\t\t\t<li>Question qid-{$fc[0]} (\"{$fc[1]}\") {$fc[2]}</li>\n";
			}
		echo "\t\t\t</ul>\n";
		echo "\t\t\t"._AC_CANNOTACTIVATE."\n";
		echo "\t\t</td>\n";
		echo "\t</tr>\n";
		echo "\t<tr>\n";
		echo "\t\t<td align='center'>\n";
		echo "\t\t\t<input type='submit' $btstyle value='"._GO_ADMIN."' onClick=\"window.open('$scriptname?sid={$_GET['sid']}', '_top')\">\n";
		echo "\t\t</td>\n";
		echo "\t</tr>\n";
		echo "</table>\n";
		exit;		
		}
	
	echo "<br />\n<table width='350' align='center' style='border: 1px solid #555555' cellpadding='1' cellspacing='0'>\n";
	echo "\t\t\t\t<tr bgcolor='#555555'><td height='4'><font size='1' face='verdana' color='white'><b>"._ACTIVATE." ($sid)</b></td></tr>\n";
	echo "\t<tr>\n";
	echo "\t\t<td align='center' bgcolor='pink'>\n";
	echo "\t\t\t<font color='red'>$setfont<b>"._WARNING."</b><br />\n";
	echo "\t\t\t"._AC_READCAREFULLY."\n";
	echo "\t\t\t</font></font>\n";
	echo "\t\t</td>\n";
	echo "\t</tr>\n";
	echo "\t<tr>\n";
	echo "\t\t<td>$setfont\n";
	echo _AC_ACTIVATE_MESSAGE1."<br /><br />\n";
	echo _AC_ACTIVATE_MESSAGE2."\n";
	echo _AC_ACTIVATE_MESSAGE3."\n";
	echo _AC_ACTIVATE_MESSAGE4."<br /><br />\n";
	echo "\t\t</td>\n";
	echo "\t</tr>\n";
	echo "\t<tr>\n";
	echo "\t\t<td align='center'>\n";
	echo "\t\t\t<input type='submit' $btstyle value=\""._AD_CANCEL."\" onclick=\"window.open('$scriptname?sid={$_GET['sid']}', '_top')\"><br />\n";
	echo "\t\t\t<input type='submit' $btstyle value=\""._AC_ACTIVATE."\" onClick=\"window.open('$scriptname?action=activate&ok=Y&sid={$_GET['sid']}', '_top')\">\n";
	echo "\t\t</td>\n";
	echo "\t</tr>\n";
	echo "</table>\n";
	echo "</body>\n</html>";
	
	}
else
	{
	$createsurvey = "CREATE TABLE survey_{$_GET['sid']} (\n";
	$createsurvey .= "  id INT(11) NOT NULL auto_increment,\n";
	$pquery = "SELECT private, datestamp FROM surveys WHERE sid={$_GET['sid']}";
	$presult=mysql_query($pquery);
	while($prow=mysql_fetch_array($presult))
		{
		if ($prow['private'] == "N") 
			{
			$createsurvey .= "  token VARCHAR(10),\n";
			$surveynotprivate="TRUE";
			}
		if ($prow['datestamp'] == "Y")
			{
			$createsurvey .= " datestamp DATETIME NOT NULL,\n";
			}
		}
	$aquery = "SELECT * FROM questions, groups WHERE questions.gid=groups.gid AND questions.sid={$_GET['sid']} ORDER BY group_name, title";
	$aresult = mysql_query($aquery);
	//echo "<br /><br />$aquery<br /><br />\n";
	while ($arow=mysql_fetch_array($aresult))
		{
		if ($arow['type'] != "M" && $arow['type'] != "A" && $arow['type'] != "B" && $arow['type'] !="C" && $arow['type'] != "E" && $arow['type'] != "F" && $arow['type'] !="P" && $arow['type'] != "R")
			{
			$createsurvey .= "  {$arow['sid']}X{$arow['gid']}X{$arow['qid']}";
			switch($arow['type'])
				{
						case "N":  //NUMERICAL
							$createsurvey .= " TINYTEXT";
							break;
						case "S":  //SHORT TEXT
							$createsurvey .= " VARCHAR(200)";
							break;
						case "L":  //DROPDOWN LIST
							$createsurvey .= " VARCHAR(5)";
							break;
						case "O": //DROPDOWN LIST WITH COMMENT
							$createsurvey .= " VARCHAR(5),\n {$arow['sid']}X{$arow['gid']}X{$arow['qid']}comment TEXT";
							break;
						case "T":  //LONG TEXT
							$createsurvey .= " TEXT";
							break;
						case "D":  //DATE
							$createsurvey .= " DATE";
							break;
						case "5":  //5 Point Choice
							$createsurvey .= " VARCHAR(1)";
							break;
						case "G":  //Gender
							$createsurvey .= " VARCHAR(1)";
							break;
						case "Y":  //YesNo
							$createsurvey .= " VARCHAR(1)";
							break;
				}
			}
		elseif ($arow['type'] == "M" || $arow['type'] == "A" || $arow['type'] == "B" || $arow['type'] == "C" || $arow['type'] == "E" || $arow['type'] == "F" || $arow['type'] == "P")
			{
			//MULTI ENTRY
			$abquery = "SELECT answers.*, questions.other FROM answers, questions WHERE answers.qid=questions.qid AND sid={$_GET['sid']} AND questions.qid={$arow['qid']} ORDER BY answers.sortorder, answers.answer";
			$abresult=mysql_query($abquery) or die ("Couldn't get perform answers query<br />$abquery<br />".mysql_error());
			while ($abrow=mysql_fetch_array($abresult))
				{
				$createsurvey .= "  {$arow['sid']}X{$arow['gid']}X{$arow['qid']}{$abrow['code']} VARCHAR(5),\n";
				if ($abrow['other']=="Y") {$alsoother="Y";}
				if ($arow['type'] == "P")
					{
					$createsurvey .= "  {$arow['sid']}X{$arow['gid']}X{$arow['qid']}{$abrow['code']}comment VARCHAR(100),\n";
					}
				}
			if ($alsoother=="Y" && ($arow['type']=="M" || $arow['type']=="P"))
				{
				$createsurvey .= " {$arow['sid']}X{$arow['gid']}X{$arow['qid']}other VARCHAR(100),\n";
				if ($arow['type']=="P")
					{
					$createsurvey .= " {$arow['sid']}X{$arow['gid']}X{$arow['qid']}othercomment VARCHAR(100),\n";
					}
				}
			}
		elseif ($arow['type'] == "R")
			{
			//MULTI ENTRY
			$abquery = "SELECT answers.*, questions.other FROM answers, questions WHERE answers.qid=questions.qid AND sid={$_GET['sid']} AND questions.qid={$arow['qid']} ORDER BY answers.sortorder, answers.answer";
			$abresult=mysql_query($abquery) or die ("Couldn't get perform answers query<br />$abquery<br />".mysql_error());
			$abcount=mysql_num_rows($abresult);
			for ($i=1; $i<=$abcount; $i++)
				{
				$createsurvey .= "  {$arow['sid']}X{$arow['gid']}X{$arow['qid']}$i VARCHAR(5),\n";
				}			
			}
		if ( substr($createsurvey, strlen($createsurvey)-2, 2) != ",\n") {$createsurvey .= ",\n";}
		}
	//$createsurvey = substr($createsurvey, 0, strlen($createsurvey)-2);
	$createsurvey .= "  INDEX(id)\n";
	$createsurvey .= ") TYPE=MyISAM;";
	//echo "<pre style='text-align: left'>$createsurvey</pre>\n"; //Debugging info
	
	$createtable=mysql_query($createsurvey) or die 
		(
		"<br />\n<table width='350' align='center' style='border: 1px solid #555555' cellpadding='1' cellspacing='0'>\n" .
		"<tr bgcolor='#555555'><td height='4'><font size='1' face='verdana' color='white'><b>"._ACTIVATE." ($sid)</b></td></tr>\n" .
		"<tr><td>\n" .
		"<font color='red'>"._AC_NOTACTIVATED."</font></center><br />\n" .
		"<center><a href='$scriptname?sid={$_GET['sid']}'>"._GO_ADMIN."</a></center>\n" .
		"DB "._ERROR.":<br />\n<font color='red'>" . mysql_error() . "</font>\n" .
		"<pre>$createsurvey</pre>\n" .
		"</td></tr></table>\n" .
		"</body>\n</html>"
		);
	
	echo "<br />\n<table width='350' align='center' style='border: 1px solid #555555' cellpadding='1' cellspacing='0'>\n";
	echo "\t\t\t\t<tr bgcolor='#555555'><td height='4'><font size='1' face='verdana' color='white'><b>"._ACTIVATE." ($sid)</b></td></tr>\n";
	echo "\t\t\t\t<tr><td align='center'><font color='green'>"._AC_ACTIVATED."<br /><br />\n";
	
	$acquery = "UPDATE surveys SET active='Y' WHERE sid={$_GET['sid']}";
	$acresult = mysql_query($acquery);
	
	if ($surveynotprivate) //This survey is tracked, and therefore a tokens table MUST exist
		{
		echo _AC_NOTPRIVATE."<br /><br />\n";
		echo "<input type='submit' value='"._AC_CREATETOKENS."' $btstyle onClick=\"window.open('tokens.php?sid={$_GET['sid']}&createtable=Y', '_top')\">\n";
		
		}
	else
		{
		echo _AC_SURVEYACTIVE."<br /><br />\n";
		echo "<input type='submit' value='"._GO_ADMIN."' $btstyle onClick=\"window.open('$scriptname?sid={$_GET['sid']}', '_top')\">\n";
		}
	echo "\t\t\t\t</td></tr></table>\n";
	echo "</body>\n</html>";
	}	
?>