<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The current user's ID, or 0 if no user is logged in.
 *
 * @return int
 */
function brewpress_user_id( $id = null ) {
	if( $id )
		return $id;
	return get_current_user_id();
}

/**
 * Return a users option from the settings page
 *
 * @return mixed
 */
function brewpress_user_option( $option = null, $single = true, $id = null ) {
	if( ! $option )
		return null;
	$opt = get_user_meta( brewpress_user_id( $id ), $option, $single );
	return $opt;
}

/**
 * Return a meta key
 *
 * @return mixed
 */
function brewpress_meta( $key = null, $id = null, $single = true ) {
	if( ! $key )
		return null;
	$id = $id ? $id : get_the_ID();
	$meta = get_post_meta( $id, $key, $single );
	return $meta;
}

/**
 * Return true or false if we are on a brewpress page
 *
 * @return mixed
 */
function is_brewpress_page() {
	global $post;
	if( is_a( $post, 'WP_Post' ) && 
		( has_shortcode( $post->post_content, 'brewpress_brewing') ) || 
		( has_shortcode( $post->post_content, 'brewpress_dashboard') ) || 
		( has_shortcode( $post->post_content, 'brewpress_settings') ) || 
		( has_shortcode( $post->post_content, 'brewpress_edit_batch') ) || 
		( has_shortcode( $post->post_content, 'brewpress_new_batch') ) ||
		( has_shortcode( $post->post_content, 'brewpress_all_batches') ) 
	) {
		return true;
	}
	return false;
}

function brewpress_all_batches_page() {
	$pages = get_pages();
	if( $pages ) {
		foreach ( $pages as $key => $page ) {
			if( is_a( $page, 'WP_Post' ) && 
				( has_shortcode( $page->post_content, 'brewpress_all_batches') ) 
			) {
				return $page->post_name;
			}
		}
		
	}
}


/**
 * Get array of our attached DS18B20 sensors
 *
 * @return array
 */
function brewpress_get_pi_sensors() {

	// dummy array for testing
    if( brewpress_testing() ) { 
        return array(
        	'Dummy 1' => 'Dummy 1', 
        	'Dummy 2' => 'Dummy 2', 
        	'Dummy 3' => 'Dummy 3',
        );
    }

	$sensors = array();
    $str = @file_get_contents('/sys/bus/w1/devices/w1_bus_master1/w1_master_slaves'); 

    if( $str === false )
        return null;

    $dev_ds18b20 = preg_split("/\\r\\n|\\r|\\n/", $str); 
    foreach( $dev_ds18b20 as $val ){ 
        if( $val!='' ){ 
            $sensors[$val] = $val; 
        } 
    }
    return $sensors;
}


/**
 * Get array of users elements as setup in settings.
 *
 * @return array
 */
function brewpress_get_elements_for_dropdown() {
	$elements = brewpress_user_option( '_brewpress_elements', true );
	if( ! $elements )
		return;
	$return = array();
	foreach ($elements as $key => $element) {
		$return[$element['name']] = $element['name'];
	}
	return $return;
}


/**
 * Get array of users pumps as setup in settings.
 *
 * @return array
 */
function brewpress_get_pumps_for_dropdown() {
	$pumps = brewpress_user_option( '_brewpress_pumps', true );
	if( ! $pumps )
		return;
	$return = array();
	foreach ($pumps as $key => $pump) {
		$return[$pump['name']] = $pump['name'];
	}
	return $return;
}

/**
 * Get array of bjcp styles.
 *
 * @return array
 */
function brewpress_get_bjcp_styles() {

	$return = array(
		"1A. American Light Lager" => "1A. American Light Lager",
		"1B. American Lager" => "1B. American Lager",
		"1C. Cream Ale" => "1C. Cream Ale",
		"1D. American Wheat Beer" => "1D. American Wheat Beer",
		"2A. International Pale Lager" => "2A. International Pale Lager",
		"2B. International Amber Lager" => "2B. International Amber Lager",
		"2C. International Dark Lager" => "2C. International Dark Lager",
		"3A. Czech Light Lager" => "3A. Czech Light Lager",
		"3B. Czech Pilsner" => "3B. Czech Pilsner",
		"3C. Czech Amber Lager" => "3C. Czech Amber Lager",
		"3D. Czech Dark Lager" => "3D. Czech Dark Lager",
		"4A. Munich Helles" => "4A. Munich Helles",
		"4B. Festbier" => "4B. Festbier",
		"4C. Helles Bock" => "4C. Helles Bock",
		"5A. German Leichtbier" => "5A. German Leichtbier",
		"5B. Kolsch" => "5B. Kolsch",
		"5C. German Exportbier" => "5C. German Exportbier",
		"5D. German Pils" => "5D. German Pils",
		"6A. Marzen" => "6A. Marzen",
		"6B. Rauchbier" => "6B. Rauchbier",
		"6C. Dunkels Bock" => "6C. Dunkels Bock",
		"7A. Vienna Lager" => "7A. Vienna Lager",
		"7B. Altbier" => "7B. Altbier",
		"7C. Kellerbier" => "7C. Kellerbier",
		"7C. Kellerbier: Munich Kellerbier" => "7C. Kellerbier: Munich Kellerbier",
		"7C. Kellerbier: Franconian Kellerbier" => "7C. Kellerbier: Franconian Kellerbier",
		"8A. Munich Dunkel" => "8A. Munich Dunkel",
		"8B. Schwarzbier" => "8B. Schwarzbier",
		"9A. Doppelbock" => "9A. Doppelbock",
		"9B. Eisbock" => "9B. Eisbock",
		"9C. Baltic Porter" => "9C. Baltic Porter",
		"10A. Weissbier" => "10A. Weissbier",
		"10B. Dunkels Weissbier" => "10B. Dunkels Weissbier",
		"10C. Weizenbock" => "10C. Weizenbock",
		"11A. Ordinary Bitter" => "11A. Ordinary Bitter",
		"11B. Best Bitter" => "11B. Best Bitter",
		"11C. Strong Bitter" => "11C. Strong Bitter",
		"12A. English Golden Ale" => "12A. English Golden Ale",
		"12B. Australian Sparkling Ale" => "12B. Australian Sparkling Ale",
		"12C. English IPA" => "12C. English IPA",
		"13A. Dark Mild" => "13A. Dark Mild",
		"13B. English Brown Ale" => "13B. English Brown Ale",
		"13C. English Porter" => "13C. English Porter",
		"14A. Scottish Light" => "14A. Scottish Light",
		"14B. Scottish Heavy" => "14B. Scottish Heavy",
		"15A. Irish Red Ale" => "15A. Irish Red Ale",
		"15B. Irish Stout" => "15B. Irish Stout",
		"15C. Irish Extra Stout" => "15C. Irish Extra Stout",
		"16A. Sweet Stout" => "16A. Sweet Stout",
		"16B. Oatmeal Stout" => "16B. Oatmeal Stout",
		"16C. Tropical Stout" => "16C. Tropical Stout",
		"16D. Foreign Export Stout" => "16D. Foreign Export Stout",
		"17A. English Strong Ale" => "17A. English Strong Ale",
		"17B. Old Ale" => "17B. Old Ale",
		"17C. Wee Heavy" => "17C. Wee Heavy",
		"17D. English Barleywine" => "17D. English Barleywine",
		"18A. Blonde Ale" => "18A. Blonde Ale",
		"18B. American Pale Ale" => "18B. American Pale Ale",
		"19A. American Amber Ale" => "19A. American Amber Ale",
		"19B. California Common" => "19B. California Common",
		"19C. American Brown Ale" => "19C. American Brown Ale",
		"20A. American Porter" => "20A. American Porter",
		"20B. American Stout" => "20B. American Stout",
		"20C. Russian Imperial Stout" => "20C. Russian Imperial Stout",
		"21A. American IPA" => "21A. American IPA",
		"21B. Specialty IPA" => "21B. Specialty IPA",
		"21B. Specialty IPA: Black IPA. " => "21B. Specialty IPA: Black IPA. ",
		"21B. Specialty IPA: Brown IPA" => "21B. Specialty IPA: Brown IPA",
		"21B. Specialty IPA: White IPA" => "21B. Specialty IPA: White IPA",
		"21B. Specialty IPA: Rye IPA" => "21B. Specialty IPA: Rye IPA",
		"21B. Specialty IPA: Belgian IPA" => "21B. Specialty IPA: Belgian IPA",
		"21B. Specialty IPA: Red IPA" => "21B. Specialty IPA: Red IPA",
		"22A. Double IPA" => "22A. Double IPA",
		"22B. American Strong Ale" => "22B. American Strong Ale",
		"22C. American Barleywine" => "22C. American Barleywine",
		"22D. Wheatwine" => "22D. Wheatwine",
		"23A. Berliner Weisse" => "23A. Berliner Weisse",
		"23B. Flanders Red Ale" => "23B. Flanders Red Ale",
		"23C. Oud Bruin" => "23C. Oud Bruin",
		"23D. Lambic" => "23D. Lambic",
		"23E. Gueuze" => "23E. Gueuze",
		"23F. Fruit Lambic" => "23F. Fruit Lambic",
		"24A. Witbier" => "24A. Witbier",
		"24B. Belgian Pale Ale" => "24B. Belgian Pale Ale",
		"24C. Biere de Garde" => "24C. Biere de Garde",
		"25A. Belgian Blond Ale" => "25A. Belgian Blond Ale",
		"25B. Saison" => "25B. Saison",
		"25C. Belgian Golden Strong Ale" => "25C. Belgian Golden Strong Ale",
		"26A. Trappist Single" => "26A. Trappist Single",
		"26B. Belgian Dubbel" => "26B. Belgian Dubbel",
		"26C. Belgian Tripel" => "26C. Belgian Tripel",
		"26D. Belgian Dark Strong Ale" => "26D. Belgian Dark Strong Ale",
		"27. Historical Beer: Gose" => "27. Historical Beer: Gose",
		"27. Historical Beer: Pivo Grodziskie" => "27. Historical Beer: Pivo Grodziskie",
		"27. Historical Beer: Lichtenhainer" => "27. Historical Beer: Lichtenhainer",
		"27. Historical Beer: Roggenbier" => "27. Historical Beer: Roggenbier",
		"27. Historical Beer: Sahti" => "27. Historical Beer: Sahti",
		"27. Historical Beer: Kentucky Common" => "27. Historical Beer: Kentucky Common",
		"27. Historical Beer: Pre-Prohibition Lager" => "27. Historical Beer: Pre-Prohibition Lager",
		"27. Historical Beer: Pre-Prohibition Porter" => "27. Historical Beer: Pre-Prohibition Porter",
		"27. Historical Beer: London Brown Ale" => "27. Historical Beer: London Brown Ale",
		"28A. Brett Beer" => "28A. Brett Beer",
		"28B. Mixed Fermentation Sour Beer" => "28B. Mixed Fermentation Sour Beer",
		"28C. Soured Fruit Beer" => "28C. Soured Fruit Beer",
		"29A. Fruit Beer" => "29A. Fruit Beer",
		"29B. Fruit and Spice Beer" => "29B. Fruit and Spice Beer",
		"29C. Speciality Fruit Beer" => "29C. Speciality Fruit Beer",
		"30A. Spice, Herb, or Vegetable Beer" => "30A. Spice, Herb, or Vegetable Beer",
		"30B. Autumn Seasonal Beer" => "30B. Autumn Seasonal Beer",
		"30C. Winter Seasonal Beer" => "30C. Winter Seasonal Beer",
		"31A. Alternative Grain Beer" => "31A. Alternative Grain Beer",
		"31B. Alternative Sugar Beer" => "31B. Alternative Sugar Beer",
		"32A. Classic Style Smoked Beer" => "32A. Classic Style Smoked Beer",
		"32B. Specialty Smoked Beer" => "32B. Specialty Smoked Beer",
		"33A. Wood-Aged Beer" => "33A. Wood-Aged Beer",
		"33B. Specialty Wood-Aged Beer" => "33B. Specialty Wood-Aged Beer",
		"34A. Clone Beer" => "34A. Clone Beer",
		"34B. Mixed-Style Beer" => "34B. Mixed-Style Beer",
		"34C. Experimental Beer" => "34C. Experimental Beer",
		"35A. Dry Mead" => "35A. Dry Mead",
		"35B. Semi-Sweet Mead" => "35B. Semi-Sweet Mead",
		"35C. Sweet Mead" => "35C. Sweet Mead",
		"36A. Cyser" => "36A. Cyser",
		"36B. Pyment" => "36B. Pyment",
		"36C. Berry Mead" => "36C. Berry Mead",
		"36D. Stone Fruit Mead" => "36D. Stone Fruit Mead",
		"36E. Melomel" => "36E. Melomel",
		"37A. Fruit and Spice Mead" => "37A. Fruit and Spice Mead",
		"37B. Spice, Herb, or Vegetable Mead" => "37B. Spice, Herb, or Vegetable Mead",
		"38A. Braggot" => "38A. Braggot",
		"38B. Historical Mead" => "38B. Historical Mead",
		"38C. Experimental Mead" => "38C. Experimental Mead",
		"39A. New World Cider" => "39A. New World Cider",
		"39B. English Cider" => "39B. English Cider",
		"39C. French Cider" => "39C. French Cider",
		"39D. New World Perry" => "39D. New World Perry",
		"39E. Traditional Perry" => "39E. Traditional Perry",
		"40A. New England Cider" => "40A. New England Cider",
		"40B. Cider with Other Fruit" => "40B. Cider with Other Fruit",
		"40C. Applewine" => "40C. Applewine",
		"40D. Ice Cider" => "40D. Ice Cider",
		"40E. Cider with Herbs/Spices" => "40E. Cider with Herbs/Spices",
		"40F. Specialty Cider/Perry" => "40F. Specialty Cider/Perry",
	);
	
	return $return;
}