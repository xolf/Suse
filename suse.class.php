<?php
/*  Suse PhP Mysql Session System
 
    SCRIPT DEVELOPED BY
    FLORIAN THEIMER (http://xolf.de)
 
    ====================================================================
    LICENSE
    ====================================================================
    Licensed under the Apache License, Version 2.0 (the "License");
    you may not use this file except in compliance with the License.
    You may obtain a copy of the License at
 
       http://www.apache.org/licenses/LICENSE-2.0
 
       Unless required by applicable law or agreed to in writing, software
       distributed under the License is distributed on an "AS IS" BASIS,
       WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
       See the License for the specific language governing permissions and
       limitations under the License.
    ====================================================================
 
    Developed by Florian Theimer for any bugs or questions email me at dashomeradio@gmail.com
 	
 	====================================================================
 	INFORMATION
 	====================================================================
 	1. Setup Suse with suse_setup($host,$user,$password,$database,$port)

 	2. Start Suse suse_start()

 	3. Add at the end of the file suse_finish()

 	4. Enjoy using Suse with $_SESSION 
*/
 

function suse_error($error){
	error_reporting(0);
	$error_start = '<style>
	body{
		margin: 0px;
	}
	.suse-error{
		padding: 5px;
		width: 100%;
		background-color: #1F84C7;
		color: #fff;
		font-family: Arial, Helvetica, Geneva, sans-serif
	}
	.suse-error h1{
		margin: 0px;
		font-size: 45px;
		text-align: center;
	}
	section.suse-error{
		color: #333;
		background-color: #fff;
	}
	section.suse-error h2{
		margin-bottom: 0px;
	}
	section.suse-error p{
		margin-top: 0px;
	}
</style>
<div class="suse-error">
	<h1>Suse Error</h1>
</div>
<section class="suse-error">';
$error_end = '</section>';
	if(is_array($error)){
		foreach ($error as $key => $value) {
			$error_more .= '<h2>'.$key.'</h2><p>'.$value.'</p>';
		}
		echo $error_start.$error_more.$error_end;
	}else{
		echo $error_start.'<h2>Unknow</h2><p>'.$error.'</p>'.$error_end;
	}
die();
}




/**
 * Function is required for Suse.
 * $host, $user, $password are required for the connection.
 * @param string $host MySql Host
 * @param string $user MySql Username
 * @param string $password MySql User Password
 * @param string $database MySql Database
 * @param string $port MySql Port
 * @param string $prefix MySql Tableprefix
 */
function suse_setup($host, $user, $password, $database, $port=3306){
	global $suse_mysql_connect,$suse_config;


	/*
	 *Setup the variables
	 */
	if(!isset($suse_config['cookie_name'])) $suse_config['cookie_name'] = 'SuseID';
	if(!isset($suse_config['lifetime'])) $suse_config['lifetime'] = 1440;
	if(!isset($suse_config['prefix'])) $suse_config['prefix'] = '';
	if(!isset($suse_config['enable_lifetime'])) $suse_config['enable_lifetime'] = TRUE;

	//Connect to Database
	$suse_mysql_connect = mysqli_connect($host,$user,$password, $database, $port);
	//Error Handling
	if (mysqli_connect_errno($suse_mysql_connect)) {
  		$error['Mysql'] = mysqli_connect_error($suse_mysql_connect);
  		suse_error($error);
	}
	//Creating Tables
		$query = 'CREATE TABLE IF NOT EXISTS `'.$suse_config['prefix'].'id` (
  				 `id` int(11) NOT NULL AUTO_INCREMENT,
  				 `hash` text NOT NULL,
  				 `expire_time` int(11) NOT NULL,
  				 `value` text NOT NULL,
  				 PRIMARY KEY (`id`)
				 )';
		mysqli_query($suse_mysql_connect,$query);
}





/**
 * Function set's Suse options.
 * Variables List:
 * COOKIE_NAME
 * LIFETIME
 * PREFIX
 * ENABLE_LIFETIME
 * @param string $var Variable Name
 * @param string $val Variable Value
 */ 
function suse_set($var, $val){
	global $suse_mysql_connect,$suse_config;
	$var = strtolower($var);
	$suse_config[$var] = $val;
}



function suse_start(){
	global $suse_mysql_connect,$suse_config,$suse_first_visit;
	$lifetime = time()+$suse_config['lifetime'];
	if(!isset($_COOKIE[$suse_config['cookie_name']])){
		$hash = md5($_SERVER['REMOTE_ADDR'].time().$_SERVER['REQUEST_TIME_FLOAT']);
		if($suse_config['enable_lifetime']){
			setcookie($suse_config['cookie_name'],$hash,$lifetime);
		}else{
			setcookie($suse_config['cookie_name'],$hash,999999999999999);
		}
		$query = 'INSERT INTO  `'.$suse_config['prefix'].'id` (
				 `id` ,
				 `hash` ,
				 `expire_time` ,
				 `value`
				 )
				 VALUES (
				 NULL ,  
				 \''.$hash.'\',  
				 \''.$lifetime.'\',  
				 \'\'
				 );
				';
		mysqli_query($suse_mysql_connect,$query);
		$suse_first_visit = $hash;
	}else{
	$query = 'SELECT * 
			  FROM  `'.$suse_config['prefix'].'id` 
			  WHERE `hash`= \''.$_COOKIE[$suse_config['cookie_name']].'\'';
	$result = mysqli_fetch_assoc(mysqli_query($suse_mysql_connect,$query));
	$_SESSION = json_decode($result['value'],true);
	}
	if($suse_config['enable_lifetime']){
		$query = 'DELETE FROM `'.$suse_config['prefix'].'id` 
		          WHERE `expire_time` < '.time().';';
		mysqli_query($suse_mysql_connect,$query);	
	}
}





function suse_finish(){
	global $suse_mysql_connect,$suse_config,$suse_first_visit;
		$lifetime = time()+$suse_config['lifetime'];
		$session_variable = json_encode($_SESSION);
	if(isset($suse_first_visit)){
		$query = 'UPDATE  `id` SET  
		 		 `expire_time` =  \''.$lifetime.'\',
				 `value` =  \''.$session_variable.'\' 
				 WHERE  `hash` = \''.$suse_first_visit.'\';';
	}else{
		$query = 'UPDATE  `id` SET  
		 		 `expire_time` =  \''.$lifetime.'\',
				 `value` =  \''.$session_variable.'\' 
				 WHERE  `hash` = \''.$_COOKIE[$suse_config['cookie_name']].'\';';
	}
	mysqli_query($suse_mysql_connect,$query);
}
