<?php
/**
 Plugin Name: Clickbank Widget
 Plugin URI: http://lunaticstudios.com/software/clickbank-widget/
 Version: 1.2
 Description: Add featured products related to your blog from Clickbank.com to your sidebar easily and earn money!
 Author: Thomas Hoefter
 Author URI: http://www.lunaticstudios.com/
 */
/*  Copyright 2009 Thomas Hoefter

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

if (version_compare(PHP_VERSION, '5.0.0.', '<'))
{
	die("Clickbank Widget requires php 5 or a greater version to work.");
}

add_option("cbw_keyword",'');
add_option("cbw_adnum",'');
add_option( 'cbw_affkey', '' );
add_option('cbad_1', '' );
add_option('cbad_2', '' );
add_option('cbad_3', '' );
add_option('cbad_4', '' );
add_option('cbad_5', '' );
add_option("cbw_image",'');
add_option("cbw_imglink",'');
add_option("cbw_title",'Featured Products');

function cbw_get_string_between($string, $start, $end){
        $string = " ".$string;
        $ini = strpos($string,$start);
        if ($ini == 0) return "";
        $ini += strlen($start);   
        $len = strpos($string,$end,$ini) - $ini;
        return substr($string,$ini,$len);
}

function cbw_get_ads($keyword,$postnumber,$descr){
	$keyword = str_replace( " ","+",$keyword );
	$sort = get_option('cbw_sortby');
	$search_url = "http://www.clickbank.com/marketplace.htm?category=-1&subcategory=-1&keywords=$keyword&sortBy=$sort&billingType=ALL&language=ALL&maxResults=10";

	// make the cURL request to $search_url
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_USERAGENT, 'Firefox (WindowsXP) - Mozilla/5.0 (Windows; U; Windows NT 5.1; en-GB; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6');
	curl_setopt($ch, CURLOPT_URL,$search_url);
	curl_setopt($ch, CURLOPT_FAILONERROR, true);
	curl_setopt($ch, CURLOPT_AUTOREFERER, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 20);
	$html= curl_exec($ch);
	if (!$html) {
		echo "<br />cURL error number:" .curl_errno($ch);
		echo "<br />cURL error:" . curl_error($ch);
		exit;
	}
	curl_close($ch); 

		$dom = new DOMDocument();
		@$dom->loadHTML($html);

	// Grab Product Links

		$xpath = new DOMXPath($dom);
		$links = $xpath->evaluate("//a[@class='siteHeader']");
		
		$xpath = new DOMXPath($dom);
		$paras = $xpath->evaluate("//td[@class='marketplace_indent']/div[@class='indent']");
			$para = $paras->item(0);
			$fullstring = $para->textContent;	
			
		$affid = get_option('cbw_affkey');
		if ($affid == '') { $affid = 'lun4tic' ;}
		update_option('cbadnr',$postnumber);
		
		for ($i = 0;  $i <= $postnumber; $i++ ) {
		$xy = get_option('cbw_skipad');
			if($i != $xy) {
				$mlink = $links->item($i);
				$url = $mlink->getAttribute('href');
				$url = str_replace("zzzzz", $affid, $url);						
				$title = $mlink->textContent;	

				if ($i == 0) {update_option("cbw_imglink",$url);}
				
				$content = cbw_get_string_between($fullstring, $title, "$/sale");				
				$titlenew = '<a href="'. $url . '">'.$title . '</a>';
				if ($descr == 'yes') {$titlenew .= ': ' . $content;}
				$z = $i+1;
				update_option("cbad_$z",$titlenew);	
				update_option("cbmainurl",$url);	
			} else {$z = $i+1;update_option("cbad_$z",'');	} 
		}		
			
}

function cbw_post() {
	$nr=get_option('cbadnr');	
	$img = get_option('cbw_image');
	$imgurl = get_option("cbw_imglink");
	$mainurl = get_option("cbmainurl");
	if ($img != '') {
		echo '<div id="mpu_banner"><a href="'.$imgurl.'"><img src="images/'.$img.'"/></a></div><ul class="list1">';
	}
	for ($i = 1;  $i <= $nr; $i++ ) { 
		$ad=get_option("cbad_$i");	
		if ($ad != '') {
			echo '<li>'.$ad.'</li>';
		}
	}
	echo '</ul>';echo '<div style="text-align:right;"><small><a href="http://lunaticstudios.com/software/clickbank-widget/" title="Free Clickbank Wordpress Plugin">cbw</a></small></div>';
}

function cbw_register_widgets() {
   register_sidebar_widget('Clickbank Widget', 'clickbank_widget');
   register_widget_control('Clickbank Widget', 'clickbank_widget_control');
}

function clickbank_widget($args) {
	extract($args);
   echo $before_widget;
   echo $before_title . get_option("cbw_title") . $after_title;
	echo '<ul>';cbw_post();echo '</ul>';
   echo $after_widget;
}

function clickbank_widget_control() {

	if (isset($_POST["cbw_keyword"])) {
		update_option('cbw_image',$_POST['cbw_image']);
		update_option('cbw_affkey',$_POST['cbw_affkey']);  
		update_option('cbw_sortby',$_POST['cbw_sortby']);  
		update_option('cbw_skipad',$_POST['cbw_skipad']);  
		update_option("cbw_title",$_POST['cbw_title']);	
		$keyword = $_POST['cbw_keyword'];
		$postnumber = $_POST['cbw_postnumber'];
		$descr = $_POST['cbw_descr'];
		update_option("cbw_keyword",$keyword);
		update_option("cbw_adnum",$postnumber);
		update_option("cbw_descr",$descr);			
		cbw_get_ads($keyword,$postnumber,$descr);
    }

		echo 'CB Username:<br/><input name="cbw_affkey" type="text" id="cbw_affkey" value="'.get_option('cbw_affkey').'"/><br/>
			  Keyword:<br/><input name="cbw_keyword" type="text" id="cbw_keyword" value="'.get_option('cbw_keyword').'"/><br/>
				Image URL:<br/><input name="cbw_image" type="text" id="cbw_image" value="'.get_option('cbw_image').'"/><br/>
				Widget Title:<br/><input name="cbw_title" type="text" id="cbw_title" value="'.get_option('cbw_title').'"/><br/>						
				Sort by:<select name="cbw_sortby" id="cbw_sortby">
                        <option value="popularity" selected >
                            Popularity
                        </option>
                        <option value="gravity"  >
                            High Gravity
                        </option>
                        <option value="gravitylow"  >
                            Low Gravity
                        </option>
                        <option value="earningsPerSale"  >
                            $ Earned/Sale
                        </option>
                        <option value="pctEarningsPerSale"  >
                            % Earned/Sale
                        </option>
                        <option value="totalRebillAmt"  >
                            Future $
                        </option>
                        <option value="totalEarningsPerSale"  >
                            Total $/sale
                        </option>
                        <option value="pctReferred"  >
                            % Referred
                        </option>
				</select><br/>	';	?>
				Number of Ads:<select name="cbw_postnumber" id="cbw_postnumber">
					<option <?php if(get_option('cbw_adnum')==1) {echo "selected";} ?>>1</option>
					<option <?php if(get_option('cbw_adnum')==2) {echo "selected";} ?>>2</option>
					<option <?php if(get_option('cbw_adnum')==3) {echo "selected";} ?>>3</option>					
					<option <?php if(get_option('cbw_adnum')==4) {echo "selected";} ?>>4</option>
					<option <?php if(get_option('cbw_adnum')==5) {echo "selected";} ?>>5</option>
				</select><br/>	
				Skip Ad #:<select name="cbw_skipad" id="cbw_skipad">
					<option <?php if(get_option('cbw_skipad')==99) {echo "selected";} ?> value="99">none</option>
					<option <?php if(get_option('cbw_skipad')==0) {echo "selected";} ?> value="0">1</option>
					<option <?php if(get_option('cbw_skipad')==1) {echo "selected";} ?> value="1">2</option>
					<option <?php if(get_option('cbw_skipad')==2) {echo "selected";} ?> value="2">3</option>					
					<option <?php if(get_option('cbw_skipad')==3) {echo "selected";} ?> value="3">4</option>
					<option <?php if(get_option('cbw_skipad')==4) {echo "selected";} ?> value="4">5</option>
				</select><br/>						
				Add Description: <input name="cbw_descr" type="checkbox" id="cbw_descr" value="yes" <?php if(get_option('cbw_descr')=='yes') {echo "checked";} ?> /><br/>
				<a href="http://lun4tic.reseller.hop.clickbank.net" target=_blank>Clickbank Sign up</a> <br/>
				<a href="http://lunaticstudios.com/software/clickbank-widget/">Plugin Documentation</a><br/>
				<a href="http://lunaticstudios.com/software/">More free Plugins</a><br/>
				
				<?php
				
}

if (function_exists('add_action')) {
   add_action('plugins_loaded', 'cbw_register_widgets');
}

?>