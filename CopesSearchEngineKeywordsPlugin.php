<?php
/* 
Plugin Name: Search Engine Keywords Plugin
Plugin URI: http://www.copesflavio.com/en/blog/wordpress-plugins
Version: v1.0
Author: <a href="http://www.copesflavio.com/">Copes Flavio</a>
Description: A plugin that you can use to correlate the key used to find your page on the search engines to
a box where you can put anything you want, for example your affiliate marketing links, or some greetings to the visitor.
It can also be used as a general Search Engine visitor gateway.
 
Copyright 2007  Copes Flavio  (email : copesc [ a t ] gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/
if (!class_exists("LandingSites")) {
	class LandingSites {

		function ls_get_refer() {
		    static $referer;
		    if (isset($referer)) return $referer;

		    // Break out quickly so we don't waste CPU cycles on non referrals
		    if (!isset($_SERVER['HTTP_REFERER']) || ($_SERVER['HTTP_REFERER'] == '')) return false;

		    $referer_info = parse_url($_SERVER['HTTP_REFERER']);
		    $referer = $referer_info['host'];

		    // Remove www. is it exists
		    if(substr($referer, 0, 4) == 'www.')
		        $referer = substr($referer, 4);

		    return $referer;
		}


		function ls_get_delim($ref) {
		    static $delim;
		    if (isset($delim)) return $delim;

		    // Search engine match array
		    // Used for fast delimiter lookup for single host search engines.
		    // Non .com Google/MSN/Yahoo referrals are checked for after this array is checked
		    // Search engines that send the term as a path are flagged with a delim of '&'

		    $search_engines = array('google.com' => 'q',
					'go.google.com' => 'q',
					'maps.google.com' => 'q',
					'local.google.com' => 'q',
					'blogsearch.google.com' => 'q',
					'search.yahoo.com' => 'p',
					'search.msn.com' => 'q',
					'msxml.excite.com' => '&',
					'a9.com' => '&',
					'search.lycos.com' => 'query',
					'alltheweb.com' => 'q',
					'search.aol.com' => 'query',
					'search.iwon.com' => 'searchfor',
					'ask.com' => 'q',
					'ask.co.uk' => 'ask',
					'search.cometsystems.com' => 'qry',
					'hotbot.com' => 'query',
					'overture.com' => 'Keywords',
					'metacrawler.com' => 'qkw',
					'search.netscape.com' => 'query',
					'looksmart.com' => 'key',
					'dpxml.webcrawler.com' => 'qkw',
					'search.earthlink.net' => 'q',
					'search.viewpoint.com' => 'k',
					'mamma.com' => 'query');

		    $delim = false;

		    // Check to see if we have a host match in our lookup array
		    if (isset($search_engines[$ref])) {
		        $delim = $search_engines[$ref];
		    } else {
		        // Lets check for referrals for international TLDs and sites with strange formats

		        // Optimizations
		        $sub13 = substr($ref, 0, 13);

		        // Search string for engine
		        if(substr($ref, 0, 7) == 'google.')
		            $delim = "q";
		        elseif($sub13 == 'search.atomz.')
		            $delim = "sp-q";
		        elseif(substr($ref, 0, 11) == 'search.msn.')
		            $delim = "q";
		        elseif($sub13 == 'search.yahoo.')
		            $delim = "p";
		        elseif(preg_match('/home\.bellsouth\.net\/s\/s\.dll/i', $ref))
		            $delim = "bellsouth";
		    }

		    return $delim;
		}
		
		
		function ls_get_terms($d) {
		    static $terms;
		    if (isset($terms)) return $terms;

		    $query_array = array();
		    $query_terms = null;

		    // A few search engines include the query as a URL path, not a variable (Excite/A9, etc)
		    if ($d == '&') {
		        $query = urldecode(substr(strrchr($_SERVER['HTTP_REFERER'], '/'), 1));
		    } else {
		        // Get raw query
		        $query = explode($d.'=', $_SERVER['HTTP_REFERER']);
		        $query = explode('&', $query[1]);
		        $query = urldecode($query[0]);
		    }

		    // Remove quotes, split into words, and format for HTML display
		    $query = str_replace("'", '', $query);
		    $query = str_replace('"', '', $query);
		    $query_array = preg_split('/[\s,\+\.]+/',$query);
		    $query_terms = implode(' ', $query_array);
		    $terms = htmlspecialchars(urldecode($query_terms));

		    return $terms;
		}

		// Return true if the referer is a search engine
		// can be called 	ls_getinfo('isref')
		//					ls_getinfo('referrer')
		//					ls_getinfo('terms')
		function ls_getinfo($what) {

		    // Did we come from a search engine? 
		    $referer = $this->ls_get_refer();
		    if (!$referer) return false;
		    $delimiter = $this->ls_get_delim($referer);

		    if($delimiter) 
		    { 
		        $terms = $this->ls_get_terms($delimiter);

		        if($what == 'isref') { return true; }
		        if($what == 'referrer') {
		            $parsed = parse_url($_SERVER['HTTP_REFERER']);
		            echo '<a href="http://'.$parsed['host'].'">'.$parsed['host'].'</a>';
		        }
		        if($what == 'terms') { return $terms; }

		    } 
		}
	}
}



if (!class_exists("CopesSearchEngineKeywordsPlugin")) {
	class CopesSearchEngineKeywordsPlugin {
		var $adminOptionsName = "CopesSearchEngineKeywordsPluginAdminOptions";
		function CopesSearchEngineKeywordsPlugin() { //constructor
			
		}
		function init() {
			$this->getAdminOptions();
		}
		//Returns an array of admin options	
		function getAdminOptions() {
			$copesALPAdminOptions = array();
			$devOptions = get_option($this->adminOptionsName);
			if (!empty($devOptions)) {
				foreach ($devOptions as $key => $option)
					$copesALPAdminOptions[$key] = $option;
			}				
			update_option($this->adminOptionsName, $copesALPAdminOptions);
			return $copesALPAdminOptions;
		}		
		
		function addContent($content = '') {
			$devOptions = $this->getAdminOptions();
			if (class_exists("LandingSites")) {
				$ls = new LandingSites();
				if ($ls->ls_getinfo('isref')) { 									
					
					for ($i = 0; $i < (count($devOptions) -1); $i++) { 
						
						if (($devOptions[$i]['choice'])==="Exact phrase") {
							if ($devOptions[$i]['key'] === $ls->ls_getinfo('terms')) {
								$content .= $devOptions[$i]['code'];
							}
						}

						if (($devOptions[$i]['choice'])==="Any of these words") { 

							$tok = strtok($devOptions[$i]['key'], " ");

							while ($tok !== false) {
								if (preg_match("/".$tok."/i", $ls->ls_getinfo('terms'))) {
									$tok = false;
									$content .= $devOptions[$i]['code'];
								} else {
									$tok = strtok(" ");
								}
							}
						}
						if (($devOptions[$i]['choice'])==="Search key contains all these items") { 
							$tok = strtok($devOptions[$i]['key'], " ");
							$ok = true;
							
							//echo $ls->ls_getinfo('terms');
							//echo $devOptions[$i]['key'];
							while ($tok !== false) {
								if (preg_match("/".$tok."/i", $ls->ls_getinfo('terms'))) {
								} else {
									$ok = false;
								}
								$tok = strtok(" ");
							}						
							if ($ok == true) {
								$content .= $devOptions[$i]['code'];
							}
						}
						
						
					}
				}			
			}
			$content = str_replace('\"','"',$content);
			return $content;
		}

		function showAdminPageHtml($devOptions) {
			?>
			<div class=wrap>
			<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
			<div class="submit">
				<input type="submit" name="update_CopesSearchEngineKeywordsPluginSettings_AddNewKey" value="<?php _e('Add New Key', 'CopesSearchEngineKeywordsPlugin') ?>" />
				<input type="submit" name="update_CopesSearchEngineKeywordsPluginSettings" value="<?php _e('Update Settings', 'CopesSearchEngineKeywordsPlugin') ?>" />
			</div>
			
			<?php
			
			for ($i = 0; $i < (count($devOptions) -1); $i++) { 
			?>

				<div class=wrap>
					<h3>Search Engine Key number <?php echo $i + 1; ?></h3>
					<input type="text" name="CopesALP_Key_<?php echo $i + 1; ?>" style="width: 80%;" value="<?php _e(apply_filters('format_to_edit',$devOptions[$i]['key']), 'CopesSearchEngineKeywordsPlugin') ?>" />
					<select name="CopesALP_Choice_<?php echo $i + 1; ?>" value="<?php _e(apply_filters('format_to_edit',$devOptions[$i]['choice']), 'CopesSearchEngineKeywordsPlugin') ?>">
						<option <?php if (($devOptions[$i]['choice'])==="Exact phrase") echo "selected"; ?>>Exact phrase</option>
						<option <?php if (($devOptions[$i]['choice'])==="Any of these words") echo "selected"; ?>>Any of these words</option>
						<option <?php if (($devOptions[$i]['choice'])==="Search key contains all these items") echo "selected"; ?>>Search key contains all these items</option>
					</select>
		
					<h3>XHTML code to display</h3>
					<?php $devOptions[$i]['code'] = str_replace('\"','"',$devOptions[$i]['code']); ?>
					
					<textarea name="CopesALP_htmlcode_<?php echo $i + 1; ?>" style="width: 80%; height: 80px;"><?php _e(apply_filters('format_to_edit',$devOptions[$i]['code']), 'CopesSearchEngineKeywordsPlugin') ?></textarea>
					<div class="submit" style="display: inline;">
						<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
							<input type="hidden" name="numberOfKeyToDelete" value="<?php echo $i; ?>"
							<input type="submit" name="update_CopesSearchEngineKeywordsPluginSettings_DeleteKey" value="<?php _e('Delete This Key', 'CopesSearchEngineKeywordsPlugin') ?>" />
						</form>
					</div>
					
				</div>
			<?php	
			}
			?>

			<div class="submit">
				<input type="submit" name="update_CopesSearchEngineKeywordsPluginSettings_AddNewKey" value="<?php _e('Add New Key', 'CopesSearchEngineKeywordsPlugin') ?>" />
			</div>

			<div class="submit">
				<input type="submit" name="update_CopesSearchEngineKeywordsPluginSettings_DeleteAllKeys" value="<?php _e('Delete All Keys', 'CopesSearchEngineKeywordsPlugin') ?>" />
				<input type="submit" name="update_CopesSearchEngineKeywordsPluginSettings" value="<?php _e('Update Settings', 'CopesSearchEngineKeywordsPlugin') ?>" />
			</div>
			</form>
			 </div>
			
			<?php
		}
	
		function countKeysInArray() {
			$devOptions = $this->getAdminOptions();
			$k = 0;				
			$count = 0;
			while ($devOptions[$k++]['key'] != null) $count++;
			return $count;
		}

		//Prints out the admin page
		function printAdminPage() {
					$devOptions = $this->getAdminOptions();
							
							
							
					if (isset($_POST['update_CopesSearchEngineKeywordsPluginSettings_DeleteAllKeys'])) { 	
						//the button Delete All Keys has been pressed
						
						$devOptions = array(
							array('key' => '', 'code' => '', 'choice' => '')); //primo 
						update_option($this->adminOptionsName, $devOptions);

						?>
						<div class="updated"><p><strong><?php _e("The Keywords have been deleted.", "CopesSearchEngineKeywordsPlugin");?></strong></p></div>
						<?php
						
					}				
					else if (isset($_POST['update_CopesSearchEngineKeywordsPluginSettings_DeleteKey'])) { 	
						//the button Delete This Key has been pressed. Let's drop the key selected
						if (isset($_POST['numberOfKeyToDelete']))  {
							function array_remove(&$array, $offset, $length=1) { 
								return array_splice($array, $offset, $length); 
							} 
						
						
							array_remove($devOptions, $_POST['numberOfKeyToDelete'] + 1);
							update_option($this->adminOptionsName, $devOptions);
							?>
							<div class="updated"><p><strong><?php _e("The Selected Keyword have been deleted.", "CopesSearchEngineKeywordsPlugin");?></strong></p></div>
							<?php
						}
					}
					else if (isset($_POST['update_CopesSearchEngineKeywordsPluginSettings_AddNewKey'])) { 	
						//the button Add New Key has been pressed
						
						$devOptions[] = array('key' => '', 'code' => '', 'choice' => '');
						update_option($this->adminOptionsName, $devOptions);

						?>
						<div class="updated"><p><strong><?php _e("A new keyword set was added.", "CopesSearchEngineKeywordsPlugin");?></strong></p></div>
						<?php
						
					}				
					else if (isset($_POST['update_CopesSearchEngineKeywordsPluginSettings'])) { 
						//the update button has been pressed
						
						
						
						/* UGLY CODE MODE ON */
						if (isset($_POST['CopesALP_Key_1']) and ($_POST['CopesALP_Key_1'] != " "))  {	$_1 =  $_POST['CopesALP_Key_1']; }
						if (isset($_POST['CopesALP_Key_2']) and ($_POST['CopesALP_Key_2'] != " "))  {	$_2 =  $_POST['CopesALP_Key_2']; }						
						if (isset($_POST['CopesALP_Key_3']) and ($_POST['CopesALP_Key_3'] != " "))  {	$_3 =  $_POST['CopesALP_Key_3']; }
						if (isset($_POST['CopesALP_Key_4']) and ($_POST['CopesALP_Key_4'] != " "))  {	$_4 =  $_POST['CopesALP_Key_4']; }
						if (isset($_POST['CopesALP_Key_5']) and ($_POST['CopesALP_Key_5'] != " "))  {	$_5 =  $_POST['CopesALP_Key_5']; }
						if (isset($_POST['CopesALP_Key_6']) and ($_POST['CopesALP_Key_6'] != " "))  {	$_6 =  $_POST['CopesALP_Key_6']; }
						if (isset($_POST['CopesALP_Key_7']) and ($_POST['CopesALP_Key_7'] != " "))  {	$_7 =  $_POST['CopesALP_Key_7']; }
						if (isset($_POST['CopesALP_Key_8']) and ($_POST['CopesALP_Key_8'] != " "))  {	$_8 =  $_POST['CopesALP_Key_8']; }
						if (isset($_POST['CopesALP_Key_9']) and ($_POST['CopesALP_Key_9'] != " "))  {	$_9 =  $_POST['CopesALP_Key_9']; }
						if (isset($_POST['CopesALP_Key_10']) and ($_POST['CopesALP_Key_10'] != " ")) {	$_10 = $_POST['CopesALP_Key_10']; }
						if (isset($_POST['CopesALP_Key_11']) and ($_POST['CopesALP_Key_11'] != " ")) {	$_11 = $_POST['CopesALP_Key_11']; }
						if (isset($_POST['CopesALP_Key_12']) and ($_POST['CopesALP_Key_12'] != " ")) {	$_12 = $_POST['CopesALP_Key_12']; }
						if (isset($_POST['CopesALP_Key_13']) and ($_POST['CopesALP_Key_13'] != " ")) {	$_13 = $_POST['CopesALP_Key_13']; }
						if (isset($_POST['CopesALP_Key_14']) and ($_POST['CopesALP_Key_14'] != " ")) {	$_14 = $_POST['CopesALP_Key_14']; }
						if (isset($_POST['CopesALP_Key_15']) and ($_POST['CopesALP_Key_15'] != " ")) {	$_15 = $_POST['CopesALP_Key_15']; }
						if (isset($_POST['CopesALP_Key_16']) and ($_POST['CopesALP_Key_16'] != " ")) {	$_16 = $_POST['CopesALP_Key_16']; }
						if (isset($_POST['CopesALP_Key_17']) and ($_POST['CopesALP_Key_17'] != " ")) {	$_17 = $_POST['CopesALP_Key_17']; }
						if (isset($_POST['CopesALP_Key_18']) and ($_POST['CopesALP_Key_18'] != " ")) {	$_18 = $_POST['CopesALP_Key_18']; }
						if (isset($_POST['CopesALP_Key_19']) and ($_POST['CopesALP_Key_19'] != " ")) {	$_19 = $_POST['CopesALP_Key_19']; }
						if (isset($_POST['CopesALP_Key_20']) and ($_POST['CopesALP_Key_20'] != " ")) {	$_20 = $_POST['CopesALP_Key_20']; }
						if (isset($_POST['CopesALP_Key_21']) and ($_POST['CopesALP_Key_21'] != " ")) {	$_21 = $_POST['CopesALP_Key_21']; }
						if (isset($_POST['CopesALP_Key_22']) and ($_POST['CopesALP_Key_22'] != " ")) {	$_22 = $_POST['CopesALP_Key_22']; }						
						if (isset($_POST['CopesALP_Key_23']) and ($_POST['CopesALP_Key_23'] != " ")) {	$_23 = $_POST['CopesALP_Key_23']; }
						if (isset($_POST['CopesALP_Key_24']) and ($_POST['CopesALP_Key_24'] != " ")) {	$_24 = $_POST['CopesALP_Key_24']; }
						if (isset($_POST['CopesALP_Key_25']) and ($_POST['CopesALP_Key_25'] != " ")) {	$_25 = $_POST['CopesALP_Key_25']; }
						if (isset($_POST['CopesALP_Key_26']) and ($_POST['CopesALP_Key_26'] != " ")) {	$_26 = $_POST['CopesALP_Key_26']; }
						if (isset($_POST['CopesALP_Key_27']) and ($_POST['CopesALP_Key_27'] != " ")) {	$_27 = $_POST['CopesALP_Key_27']; }
						if (isset($_POST['CopesALP_Key_28']) and ($_POST['CopesALP_Key_28'] != " ")) {	$_28 = $_POST['CopesALP_Key_28']; }
						if (isset($_POST['CopesALP_Key_29']) and ($_POST['CopesALP_Key_29'] != " ")) {	$_29 = $_POST['CopesALP_Key_29']; }
						if (isset($_POST['CopesALP_Key_30']) and ($_POST['CopesALP_Key_30'] != " ")) {	$_30 = $_POST['CopesALP_Key_30']; }
						if (isset($_POST['CopesALP_Key_31']) and ($_POST['CopesALP_Key_31'] != " "))  {	$_31 =  $_POST['CopesALP_Key_31']; }
						if (isset($_POST['CopesALP_Key_32']) and ($_POST['CopesALP_Key_32'] != " "))  {	$_32 =  $_POST['CopesALP_Key_32']; }						
						if (isset($_POST['CopesALP_Key_33']) and ($_POST['CopesALP_Key_33'] != " "))  {	$_33 =  $_POST['CopesALP_Key_33']; }
						if (isset($_POST['CopesALP_Key_34']) and ($_POST['CopesALP_Key_34'] != " "))  {	$_34 =  $_POST['CopesALP_Key_34']; }
						if (isset($_POST['CopesALP_Key_35']) and ($_POST['CopesALP_Key_35'] != " "))  {	$_35 =  $_POST['CopesALP_Key_35']; }
						if (isset($_POST['CopesALP_Key_36']) and ($_POST['CopesALP_Key_36'] != " "))  {	$_36 =  $_POST['CopesALP_Key_36']; }
						if (isset($_POST['CopesALP_Key_37']) and ($_POST['CopesALP_Key_37'] != " "))  {	$_37 =  $_POST['CopesALP_Key_37']; }
						if (isset($_POST['CopesALP_Key_38']) and ($_POST['CopesALP_Key_38'] != " "))  {	$_38 =  $_POST['CopesALP_Key_38']; }
						if (isset($_POST['CopesALP_Key_39']) and ($_POST['CopesALP_Key_39'] != " "))  {	$_39 =  $_POST['CopesALP_Key_39']; }
						if (isset($_POST['CopesALP_Key_40']) and ($_POST['CopesALP_Key_40'] != " "))  {	$_40 = $_POST['CopesALP_Key_40']; }
						if (isset($_POST['CopesALP_Key_41']) and ($_POST['CopesALP_Key_41'] != " "))  {	$_41 =  $_POST['CopesALP_Key_41']; }
						if (isset($_POST['CopesALP_Key_42']) and ($_POST['CopesALP_Key_42'] != " "))  {	$_42 =  $_POST['CopesALP_Key_42']; }						
						if (isset($_POST['CopesALP_Key_43']) and ($_POST['CopesALP_Key_43'] != " "))  {	$_43 =  $_POST['CopesALP_Key_43']; }
						if (isset($_POST['CopesALP_Key_44']) and ($_POST['CopesALP_Key_44'] != " "))  {	$_44 =  $_POST['CopesALP_Key_44']; }
						if (isset($_POST['CopesALP_Key_45']) and ($_POST['CopesALP_Key_45'] != " "))  {	$_45 =  $_POST['CopesALP_Key_45']; }
						if (isset($_POST['CopesALP_Key_46']) and ($_POST['CopesALP_Key_46'] != " "))  {	$_46 =  $_POST['CopesALP_Key_46']; }
						if (isset($_POST['CopesALP_Key_47']) and ($_POST['CopesALP_Key_47'] != " "))  {	$_47 =  $_POST['CopesALP_Key_47']; }
						if (isset($_POST['CopesALP_Key_48']) and ($_POST['CopesALP_Key_48'] != " "))  {	$_48 =  $_POST['CopesALP_Key_48']; }
						if (isset($_POST['CopesALP_Key_49']) and ($_POST['CopesALP_Key_49'] != " "))  {	$_49 =  $_POST['CopesALP_Key_49']; }
						if (isset($_POST['CopesALP_Key_50']) and ($_POST['CopesALP_Key_50'] != " "))  {	$_50 = $_POST['CopesALP_Key_50']; }
						if (isset($_POST['CopesALP_Key_51']) and ($_POST['CopesALP_Key_51'] != " "))  {	$_51 =  $_POST['CopesALP_Key_51']; }
						if (isset($_POST['CopesALP_Key_52']) and ($_POST['CopesALP_Key_52'] != " "))  {	$_52 =  $_POST['CopesALP_Key_52']; }						
						if (isset($_POST['CopesALP_Key_53']) and ($_POST['CopesALP_Key_53'] != " "))  {	$_53 =  $_POST['CopesALP_Key_53']; }
						if (isset($_POST['CopesALP_Key_54']) and ($_POST['CopesALP_Key_54'] != " "))  {	$_54 =  $_POST['CopesALP_Key_54']; }
						if (isset($_POST['CopesALP_Key_55']) and ($_POST['CopesALP_Key_55'] != " "))  {	$_55 =  $_POST['CopesALP_Key_55']; }
						if (isset($_POST['CopesALP_Key_56']) and ($_POST['CopesALP_Key_56'] != " "))  {	$_56 =  $_POST['CopesALP_Key_56']; }
						if (isset($_POST['CopesALP_Key_57']) and ($_POST['CopesALP_Key_57'] != " "))  {	$_57 =  $_POST['CopesALP_Key_57']; }
						if (isset($_POST['CopesALP_Key_58']) and ($_POST['CopesALP_Key_58'] != " "))  {	$_58 =  $_POST['CopesALP_Key_58']; }
						if (isset($_POST['CopesALP_Key_59']) and ($_POST['CopesALP_Key_59'] != " "))  {	$_59 =  $_POST['CopesALP_Key_59']; }
						if (isset($_POST['CopesALP_Key_60']) and ($_POST['CopesALP_Key_60'] != " "))  {	$_60 = $_POST['CopesALP_Key_60']; }
						
						for ($i = 0; $i < (count($devOptions) -1); $i++) { 
							$j = $i+1;
							//I tried using dynamic variables with $_POST but it didn't work due to the parenthesis in the variable $_POST (i think)
							$var = "_$j"; 
							if ($var != " ") {						
								$devOptions[$i]['key'] = "${$var}"; 
							}
						}	


						

						if (isset($_POST['CopesALP_htmlcode_1']) and ($_POST['CopesALP_htmlcode_1'] != " "))  {	$_1 =  $_POST['CopesALP_htmlcode_1']; }
						if (isset($_POST['CopesALP_htmlcode_2']) and ($_POST['CopesALP_htmlcode_2'] != " "))  {	$_2 =  $_POST['CopesALP_htmlcode_2']; }						
						if (isset($_POST['CopesALP_htmlcode_3']) and ($_POST['CopesALP_htmlcode_3'] != " "))  {	$_3 =  $_POST['CopesALP_htmlcode_3']; }
						if (isset($_POST['CopesALP_htmlcode_4']) and ($_POST['CopesALP_htmlcode_4'] != " "))  {	$_4 =  $_POST['CopesALP_htmlcode_4']; }
						if (isset($_POST['CopesALP_htmlcode_5']) and ($_POST['CopesALP_htmlcode_5'] != " "))  {	$_5 =  $_POST['CopesALP_htmlcode_5']; }
						if (isset($_POST['CopesALP_htmlcode_6']) and ($_POST['CopesALP_htmlcode_6'] != " "))  {	$_6 =  $_POST['CopesALP_htmlcode_6']; }
						if (isset($_POST['CopesALP_htmlcode_7']) and ($_POST['CopesALP_htmlcode_7'] != " "))  {	$_7 =  $_POST['CopesALP_htmlcode_7']; }
						if (isset($_POST['CopesALP_htmlcode_8']) and ($_POST['CopesALP_htmlcode_8'] != " "))  {	$_8 =  $_POST['CopesALP_htmlcode_8']; }
						if (isset($_POST['CopesALP_htmlcode_9']) and ($_POST['CopesALP_htmlcode_9'] != " "))  {	$_9 =  $_POST['CopesALP_htmlcode_9']; }
						if (isset($_POST['CopesALP_htmlcode_10']) and ($_POST['CopesALP_htmlcode_10'] != " ")) {	$_10 = $_POST['CopesALP_htmlcode_10']; }
						if (isset($_POST['CopesALP_htmlcode_11']) and ($_POST['CopesALP_htmlcode_11'] != " ")) {	$_11 = $_POST['CopesALP_htmlcode_11']; }
						if (isset($_POST['CopesALP_htmlcode_12']) and ($_POST['CopesALP_htmlcode_12'] != " ")) {	$_12 = $_POST['CopesALP_htmlcode_12']; }
						if (isset($_POST['CopesALP_htmlcode_13']) and ($_POST['CopesALP_htmlcode_13'] != " ")) {	$_13 = $_POST['CopesALP_htmlcode_13']; }
						if (isset($_POST['CopesALP_htmlcode_14']) and ($_POST['CopesALP_htmlcode_14'] != " ")) {	$_14 = $_POST['CopesALP_htmlcode_14']; }
						if (isset($_POST['CopesALP_htmlcode_15']) and ($_POST['CopesALP_htmlcode_15'] != " ")) {	$_15 = $_POST['CopesALP_htmlcode_15']; }
						if (isset($_POST['CopesALP_htmlcode_16']) and ($_POST['CopesALP_htmlcode_16'] != " ")) {	$_16 = $_POST['CopesALP_htmlcode_16']; }
						if (isset($_POST['CopesALP_htmlcode_17']) and ($_POST['CopesALP_htmlcode_17'] != " ")) {	$_17 = $_POST['CopesALP_htmlcode_17']; }
						if (isset($_POST['CopesALP_htmlcode_18']) and ($_POST['CopesALP_htmlcode_18'] != " ")) {	$_18 = $_POST['CopesALP_htmlcode_18']; }
						if (isset($_POST['CopesALP_htmlcode_19']) and ($_POST['CopesALP_htmlcode_19'] != " ")) {	$_19 = $_POST['CopesALP_htmlcode_19']; }
						if (isset($_POST['CopesALP_htmlcode_20']) and ($_POST['CopesALP_htmlcode_20'] != " ")) {	$_20 = $_POST['CopesALP_htmlcode_20']; }
						if (isset($_POST['CopesALP_htmlcode_21']) and ($_POST['CopesALP_htmlcode_21'] != " ")) {	$_21 = $_POST['CopesALP_htmlcode_21']; }
						if (isset($_POST['CopesALP_htmlcode_22']) and ($_POST['CopesALP_htmlcode_22'] != " ")) {	$_22 = $_POST['CopesALP_htmlcode_22']; }						
						if (isset($_POST['CopesALP_htmlcode_23']) and ($_POST['CopesALP_htmlcode_23'] != " ")) {	$_23 = $_POST['CopesALP_htmlcode_23']; }
						if (isset($_POST['CopesALP_htmlcode_24']) and ($_POST['CopesALP_htmlcode_24'] != " ")) {	$_24 = $_POST['CopesALP_htmlcode_24']; }
						if (isset($_POST['CopesALP_htmlcode_25']) and ($_POST['CopesALP_htmlcode_25'] != " ")) {	$_25 = $_POST['CopesALP_htmlcode_25']; }
						if (isset($_POST['CopesALP_htmlcode_26']) and ($_POST['CopesALP_htmlcode_26'] != " ")) {	$_26 = $_POST['CopesALP_htmlcode_26']; }
						if (isset($_POST['CopesALP_htmlcode_27']) and ($_POST['CopesALP_htmlcode_27'] != " ")) {	$_27 = $_POST['CopesALP_htmlcode_27']; }
						if (isset($_POST['CopesALP_htmlcode_28']) and ($_POST['CopesALP_htmlcode_28'] != " ")) {	$_28 = $_POST['CopesALP_htmlcode_28']; }
						if (isset($_POST['CopesALP_htmlcode_29']) and ($_POST['CopesALP_htmlcode_29'] != " ")) {	$_29 = $_POST['CopesALP_htmlcode_29']; }
						if (isset($_POST['CopesALP_htmlcode_30']) and ($_POST['CopesALP_htmlcode_30'] != " ")) {	$_30 = $_POST['CopesALP_htmlcode_30']; }
						if (isset($_POST['CopesALP_htmlcode_31']) and ($_POST['CopesALP_htmlcode_31'] != " "))  {	$_31 =  $_POST['CopesALP_htmlcode_31']; }
						if (isset($_POST['CopesALP_htmlcode_32']) and ($_POST['CopesALP_htmlcode_32'] != " "))  {	$_32 =  $_POST['CopesALP_htmlcode_32']; }						
						if (isset($_POST['CopesALP_htmlcode_33']) and ($_POST['CopesALP_htmlcode_33'] != " "))  {	$_33 =  $_POST['CopesALP_htmlcode_33']; }
						if (isset($_POST['CopesALP_htmlcode_34']) and ($_POST['CopesALP_htmlcode_34'] != " "))  {	$_34 =  $_POST['CopesALP_htmlcode_34']; }
						if (isset($_POST['CopesALP_htmlcode_35']) and ($_POST['CopesALP_htmlcode_35'] != " "))  {	$_35 =  $_POST['CopesALP_htmlcode_35']; }
						if (isset($_POST['CopesALP_htmlcode_36']) and ($_POST['CopesALP_htmlcode_36'] != " "))  {	$_36 =  $_POST['CopesALP_htmlcode_36']; }
						if (isset($_POST['CopesALP_htmlcode_37']) and ($_POST['CopesALP_htmlcode_37'] != " "))  {	$_37 =  $_POST['CopesALP_htmlcode_37']; }
						if (isset($_POST['CopesALP_htmlcode_38']) and ($_POST['CopesALP_htmlcode_38'] != " "))  {	$_38 =  $_POST['CopesALP_htmlcode_38']; }
						if (isset($_POST['CopesALP_htmlcode_39']) and ($_POST['CopesALP_htmlcode_39'] != " "))  {	$_39 =  $_POST['CopesALP_htmlcode_39']; }
						if (isset($_POST['CopesALP_htmlcode_40']) and ($_POST['CopesALP_htmlcode_40'] != " "))  {	$_40 = $_POST['CopesALP_htmlcode_40']; }
						if (isset($_POST['CopesALP_htmlcode_41']) and ($_POST['CopesALP_htmlcode_41'] != " "))  {	$_41 =  $_POST['CopesALP_htmlcode_41']; }
						if (isset($_POST['CopesALP_htmlcode_42']) and ($_POST['CopesALP_htmlcode_42'] != " "))  {	$_42 =  $_POST['CopesALP_htmlcode_42']; }						
						if (isset($_POST['CopesALP_htmlcode_43']) and ($_POST['CopesALP_htmlcode_43'] != " "))  {	$_43 =  $_POST['CopesALP_htmlcode_43']; }
						if (isset($_POST['CopesALP_htmlcode_44']) and ($_POST['CopesALP_htmlcode_44'] != " "))  {	$_44 =  $_POST['CopesALP_htmlcode_44']; }
						if (isset($_POST['CopesALP_htmlcode_45']) and ($_POST['CopesALP_htmlcode_45'] != " "))  {	$_45 =  $_POST['CopesALP_htmlcode_45']; }
						if (isset($_POST['CopesALP_htmlcode_46']) and ($_POST['CopesALP_htmlcode_46'] != " "))  {	$_46 =  $_POST['CopesALP_htmlcode_46']; }
						if (isset($_POST['CopesALP_htmlcode_47']) and ($_POST['CopesALP_htmlcode_47'] != " "))  {	$_47 =  $_POST['CopesALP_htmlcode_47']; }
						if (isset($_POST['CopesALP_htmlcode_48']) and ($_POST['CopesALP_htmlcode_48'] != " "))  {	$_48 =  $_POST['CopesALP_htmlcode_48']; }
						if (isset($_POST['CopesALP_htmlcode_49']) and ($_POST['CopesALP_htmlcode_49'] != " "))  {	$_49 =  $_POST['CopesALP_htmlcode_49']; }
						if (isset($_POST['CopesALP_htmlcode_50']) and ($_POST['CopesALP_htmlcode_50'] != " "))  {	$_50 = $_POST['CopesALP_htmlcode_50']; }
						if (isset($_POST['CopesALP_htmlcode_51']) and ($_POST['CopesALP_htmlcode_51'] != " "))  {	$_51 =  $_POST['CopesALP_htmlcode_51']; }
						if (isset($_POST['CopesALP_htmlcode_52']) and ($_POST['CopesALP_htmlcode_52'] != " "))  {	$_52 =  $_POST['CopesALP_htmlcode_52']; }						
						if (isset($_POST['CopesALP_htmlcode_53']) and ($_POST['CopesALP_htmlcode_53'] != " "))  {	$_53 =  $_POST['CopesALP_htmlcode_53']; }
						if (isset($_POST['CopesALP_htmlcode_54']) and ($_POST['CopesALP_htmlcode_54'] != " "))  {	$_54 =  $_POST['CopesALP_htmlcode_54']; }
						if (isset($_POST['CopesALP_htmlcode_55']) and ($_POST['CopesALP_htmlcode_55'] != " "))  {	$_55 =  $_POST['CopesALP_htmlcode_55']; }
						if (isset($_POST['CopesALP_htmlcode_56']) and ($_POST['CopesALP_htmlcode_56'] != " "))  {	$_56 =  $_POST['CopesALP_htmlcode_56']; }
						if (isset($_POST['CopesALP_htmlcode_57']) and ($_POST['CopesALP_htmlcode_57'] != " "))  {	$_57 =  $_POST['CopesALP_htmlcode_57']; }
						if (isset($_POST['CopesALP_htmlcode_58']) and ($_POST['CopesALP_htmlcode_58'] != " "))  {	$_58 =  $_POST['CopesALP_htmlcode_58']; }
						if (isset($_POST['CopesALP_htmlcode_59']) and ($_POST['CopesALP_htmlcode_59'] != " "))  {	$_59 =  $_POST['CopesALP_htmlcode_59']; }
						if (isset($_POST['CopesALP_htmlcode_60']) and ($_POST['CopesALP_htmlcode_60'] != " "))  {	$_60 = $_POST['CopesALP_htmlcode_60']; }
						
											
						for ($i = 0; $i < (count($devOptions) -1); $i++) { 	
							$j = $i+1;
							$var = "_$j"; 
							if ($var != " ") {						
								$devOptions[$i]['code'] = "${$var}"; 
							}
						}
						
				
						if (isset($_POST['CopesALP_Choice_1']) and ($_POST['CopesALP_Choice_1'] != " "))  {	$_1 =  $_POST['CopesALP_Choice_1']; }
						if (isset($_POST['CopesALP_Choice_2']) and ($_POST['CopesALP_Choice_2'] != " "))  {	$_2 =  $_POST['CopesALP_Choice_2']; }						
						if (isset($_POST['CopesALP_Choice_3']) and ($_POST['CopesALP_Choice_3'] != " "))  {	$_3 =  $_POST['CopesALP_Choice_3']; }
						if (isset($_POST['CopesALP_Choice_4']) and ($_POST['CopesALP_Choice_4'] != " "))  {	$_4 =  $_POST['CopesALP_Choice_4']; }
						if (isset($_POST['CopesALP_Choice_5']) and ($_POST['CopesALP_Choice_5'] != " "))  {	$_5 =  $_POST['CopesALP_Choice_5']; }
						if (isset($_POST['CopesALP_Choice_6']) and ($_POST['CopesALP_Choice_6'] != " "))  {	$_6 =  $_POST['CopesALP_Choice_6']; }
						if (isset($_POST['CopesALP_Choice_7']) and ($_POST['CopesALP_Choice_7'] != " "))  {	$_7 =  $_POST['CopesALP_Choice_7']; }
						if (isset($_POST['CopesALP_Choice_8']) and ($_POST['CopesALP_Choice_8'] != " "))  {	$_8 =  $_POST['CopesALP_Choice_8']; }
						if (isset($_POST['CopesALP_Choice_9']) and ($_POST['CopesALP_Choice_9'] != " "))  {	$_9 =  $_POST['CopesALP_Choice_9']; }
						if (isset($_POST['CopesALP_Choice_10']) and ($_POST['CopesALP_Choice_10'] != " ")) {	$_10 = $_POST['CopesALP_Choice_10']; }
						if (isset($_POST['CopesALP_Choice_11']) and ($_POST['CopesALP_Choice_11'] != " ")) {	$_11 = $_POST['CopesALP_Choice_11']; }
						if (isset($_POST['CopesALP_Choice_12']) and ($_POST['CopesALP_Choice_12'] != " ")) {	$_12 = $_POST['CopesALP_Choice_12']; }
						if (isset($_POST['CopesALP_Choice_13']) and ($_POST['CopesALP_Choice_13'] != " ")) {	$_13 = $_POST['CopesALP_Choice_13']; }
						if (isset($_POST['CopesALP_Choice_14']) and ($_POST['CopesALP_Choice_14'] != " ")) {	$_14 = $_POST['CopesALP_Choice_14']; }
						if (isset($_POST['CopesALP_Choice_15']) and ($_POST['CopesALP_Choice_15'] != " ")) {	$_15 = $_POST['CopesALP_Choice_15']; }
						if (isset($_POST['CopesALP_Choice_16']) and ($_POST['CopesALP_Choice_16'] != " ")) {	$_16 = $_POST['CopesALP_Choice_16']; }
						if (isset($_POST['CopesALP_Choice_17']) and ($_POST['CopesALP_Choice_17'] != " ")) {	$_17 = $_POST['CopesALP_Choice_17']; }
						if (isset($_POST['CopesALP_Choice_18']) and ($_POST['CopesALP_Choice_18'] != " ")) {	$_18 = $_POST['CopesALP_Choice_18']; }
						if (isset($_POST['CopesALP_Choice_19']) and ($_POST['CopesALP_Choice_19'] != " ")) {	$_19 = $_POST['CopesALP_Choice_19']; }
						if (isset($_POST['CopesALP_Choice_20']) and ($_POST['CopesALP_Choice_20'] != " ")) {	$_20 = $_POST['CopesALP_Choice_20']; }
						if (isset($_POST['CopesALP_Choice_21']) and ($_POST['CopesALP_Choice_21'] != " ")) {	$_21 = $_POST['CopesALP_Choice_21']; }
						if (isset($_POST['CopesALP_Choice_22']) and ($_POST['CopesALP_Choice_22'] != " ")) {	$_22 = $_POST['CopesALP_Choice_22']; }						
						if (isset($_POST['CopesALP_Choice_23']) and ($_POST['CopesALP_Choice_23'] != " ")) {	$_23 = $_POST['CopesALP_Choice_23']; }
						if (isset($_POST['CopesALP_Choice_24']) and ($_POST['CopesALP_Choice_24'] != " ")) {	$_24 = $_POST['CopesALP_Choice_24']; }
						if (isset($_POST['CopesALP_Choice_25']) and ($_POST['CopesALP_Choice_25'] != " ")) {	$_25 = $_POST['CopesALP_Choice_25']; }
						if (isset($_POST['CopesALP_Choice_26']) and ($_POST['CopesALP_Choice_26'] != " ")) {	$_26 = $_POST['CopesALP_Choice_26']; }
						if (isset($_POST['CopesALP_Choice_27']) and ($_POST['CopesALP_Choice_27'] != " ")) {	$_27 = $_POST['CopesALP_Choice_27']; }
						if (isset($_POST['CopesALP_Choice_28']) and ($_POST['CopesALP_Choice_28'] != " ")) {	$_28 = $_POST['CopesALP_Choice_28']; }
						if (isset($_POST['CopesALP_Choice_29']) and ($_POST['CopesALP_Choice_29'] != " ")) {	$_29 = $_POST['CopesALP_Choice_29']; }
						if (isset($_POST['CopesALP_Choice_30']) and ($_POST['CopesALP_Choice_30'] != " ")) {	$_30 = $_POST['CopesALP_Choice_30']; }
						if (isset($_POST['CopesALP_Choice_31']) and ($_POST['CopesALP_Choice_31'] != " "))  {	$_31 =  $_POST['CopesALP_Choice_31']; }
						if (isset($_POST['CopesALP_Choice_32']) and ($_POST['CopesALP_Choice_32'] != " "))  {	$_32 =  $_POST['CopesALP_Choice_32']; }						
						if (isset($_POST['CopesALP_Choice_33']) and ($_POST['CopesALP_Choice_33'] != " "))  {	$_33 =  $_POST['CopesALP_Choice_33']; }
						if (isset($_POST['CopesALP_Choice_34']) and ($_POST['CopesALP_Choice_34'] != " "))  {	$_34 =  $_POST['CopesALP_Choice_34']; }
						if (isset($_POST['CopesALP_Choice_35']) and ($_POST['CopesALP_Choice_35'] != " "))  {	$_35 =  $_POST['CopesALP_Choice_35']; }
						if (isset($_POST['CopesALP_Choice_36']) and ($_POST['CopesALP_Choice_36'] != " "))  {	$_36 =  $_POST['CopesALP_Choice_36']; }
						if (isset($_POST['CopesALP_Choice_37']) and ($_POST['CopesALP_Choice_37'] != " "))  {	$_37 =  $_POST['CopesALP_Choice_37']; }
						if (isset($_POST['CopesALP_Choice_38']) and ($_POST['CopesALP_Choice_38'] != " "))  {	$_38 =  $_POST['CopesALP_Choice_38']; }
						if (isset($_POST['CopesALP_Choice_39']) and ($_POST['CopesALP_Choice_39'] != " "))  {	$_39 =  $_POST['CopesALP_Choice_39']; }
						if (isset($_POST['CopesALP_Choice_40']) and ($_POST['CopesALP_Choice_40'] != " "))  {	$_40 = $_POST['CopesALP_Choice_40']; }
						if (isset($_POST['CopesALP_Choice_41']) and ($_POST['CopesALP_Choice_41'] != " "))  {	$_41 =  $_POST['CopesALP_Choice_41']; }
						if (isset($_POST['CopesALP_Choice_42']) and ($_POST['CopesALP_Choice_42'] != " "))  {	$_42 =  $_POST['CopesALP_Choice_42']; }						
						if (isset($_POST['CopesALP_Choice_43']) and ($_POST['CopesALP_Choice_43'] != " "))  {	$_43 =  $_POST['CopesALP_Choice_43']; }
						if (isset($_POST['CopesALP_Choice_44']) and ($_POST['CopesALP_Choice_44'] != " "))  {	$_44 =  $_POST['CopesALP_Choice_44']; }
						if (isset($_POST['CopesALP_Choice_45']) and ($_POST['CopesALP_Choice_45'] != " "))  {	$_45 =  $_POST['CopesALP_Choice_45']; }
						if (isset($_POST['CopesALP_Choice_46']) and ($_POST['CopesALP_Choice_46'] != " "))  {	$_46 =  $_POST['CopesALP_Choice_46']; }
						if (isset($_POST['CopesALP_Choice_47']) and ($_POST['CopesALP_Choice_47'] != " "))  {	$_47 =  $_POST['CopesALP_Choice_47']; }
						if (isset($_POST['CopesALP_Choice_48']) and ($_POST['CopesALP_Choice_48'] != " "))  {	$_48 =  $_POST['CopesALP_Choice_48']; }
						if (isset($_POST['CopesALP_Choice_49']) and ($_POST['CopesALP_Choice_49'] != " "))  {	$_49 =  $_POST['CopesALP_Choice_49']; }
						if (isset($_POST['CopesALP_Choice_50']) and ($_POST['CopesALP_Choice_50'] != " "))  {	$_50 = $_POST['CopesALP_Choice_50']; }
						if (isset($_POST['CopesALP_Choice_51']) and ($_POST['CopesALP_Choice_51'] != " "))  {	$_51 =  $_POST['CopesALP_Choice_51']; }
						if (isset($_POST['CopesALP_Choice_52']) and ($_POST['CopesALP_Choice_52'] != " "))  {	$_52 =  $_POST['CopesALP_Choice_52']; }						
						if (isset($_POST['CopesALP_Choice_53']) and ($_POST['CopesALP_Choice_53'] != " "))  {	$_53 =  $_POST['CopesALP_Choice_53']; }
						if (isset($_POST['CopesALP_Choice_54']) and ($_POST['CopesALP_Choice_54'] != " "))  {	$_54 =  $_POST['CopesALP_Choice_54']; }
						if (isset($_POST['CopesALP_Choice_55']) and ($_POST['CopesALP_Choice_55'] != " "))  {	$_55 =  $_POST['CopesALP_Choice_55']; }
						if (isset($_POST['CopesALP_Choice_56']) and ($_POST['CopesALP_Choice_56'] != " "))  {	$_56 =  $_POST['CopesALP_Choice_56']; }
						if (isset($_POST['CopesALP_Choice_57']) and ($_POST['CopesALP_Choice_57'] != " "))  {	$_57 =  $_POST['CopesALP_Choice_57']; }
						if (isset($_POST['CopesALP_Choice_58']) and ($_POST['CopesALP_Choice_58'] != " "))  {	$_58 =  $_POST['CopesALP_Choice_58']; }
						if (isset($_POST['CopesALP_Choice_59']) and ($_POST['CopesALP_Choice_59'] != " "))  {	$_59 =  $_POST['CopesALP_Choice_59']; }
						if (isset($_POST['CopesALP_Choice_60']) and ($_POST['CopesALP_Choice_60'] != " "))  {	$_60 = $_POST['CopesALP_Choice_60']; }

						for ($i = 0; $i < (count($devOptions) -1); $i++) { 
							$j = $i+1;
							//I tried using dynamic variables with $_POST but it didn't work due to the parenthesis in the variable $_POST (i think)
							$var = "_$j"; 
							if ($var != " ") {						
								$devOptions[$i]['choice'] = "${$var}"; 
							}
						}
				
						update_option($this->adminOptionsName, $devOptions);
						?>
						<div class="updated"><p><strong><?php _e("Settings Updated.", "CopesSearchEngineKeywordsPlugin");?></strong></p></div>
						<?php
					
					} 
					$this->showAdminPageHtml($devOptions);
					
		}//End function printAdminPage()
				
	}
} //End Class CopesSearchEngineKeywordsPlugin






if (class_exists("CopesSearchEngineKeywordsPlugin")) {
	$copesSEK = new CopesSearchEngineKeywordsPlugin();
}

//Initialize the admin panel
if (!function_exists("CopesSearchEngineKeywordsPlugin_ap")) {
	function CopesSearchEngineKeywordsPlugin_ap() {
		global $copesSEK;
		if (!isset($copesSEK)) {
			return;
		}
		if (function_exists('add_options_page')) {
	add_options_page('Search Engine Keywords Plugin', 'Search Engine Keywords Plugin', 9, basename(__FILE__), array(&$copesSEK, 'printAdminPage'));
		}
	}	
}

//Actions and Filters	
if (isset($copesSEK)) {
	//Actions
	add_action('admin_menu', 'CopesSearchEngineKeywordsPlugin_ap');
	add_action('activate_copesALP-plugin-series/copesALP-plugin-series.php',  array(&$copesSEK, 'init'));
	//Filters
}
?>