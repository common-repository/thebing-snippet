<?php

/**
 * Class Thebing_Wordpress
 * @author Thebing Services GmbH
 */
class Thebing_Wordpress {

	static $result = [];
	static $content;
	
	/**
	 * Verfügbare Typen: tsFeedback, tsPlacementTest, tsRegistrationForm, default
	 * Verfügbare Attribute: type, server, combinationkey, templatekey, key, language, currencyid, currencyiso
	 *
	 * @param array $attributes
	 *     type = tsFeedback -> $attributes('type', 'server', 'key', 'language')
	 * 	   type = tsPlacementTest -> $attributes('type', 'server', 'key', ['language'], ['currencyid'], ['currencyiso'])
	 * 	   type = tsRegistrationForm -> $attributes('type', 'server', ['key'], ['language'])
	 * 	   type = default -> $attributes('type', 'server', 'combinationkey', 'templatekey')
	 *
	 * @return string
	 */
	static public function getContent($attributes) {

		$cacheKey = implode('-', $attributes);
		
		if(isset(self::$content[$cacheKey])) {
			return self::$content[$cacheKey];
		}
		
		$tagAttributes = shortcode_atts(array(
			'type' => 'default',
			'server' => '',
			'combinationkey' => '',
			'templatekey' => '',
			'key' => '',
			'language' => '',
			'currencyid' => '',
			'currencyiso' => ''
		), $attributes);

		$sConfigUrl = get_option('thebingsnippet_url');

		if(
			empty($tagAttributes['server']) &&
			!empty($sConfigUrl)
		) {
			$tagAttributes['server'] = $sConfigUrl;
		}

		$sContent = '';
		switch($tagAttributes['type']) {
			case 'tsFeedback':
				$sContent = self::getTsFeedback($tagAttributes['server'], $tagAttributes['key'], $tagAttributes['language']);
				break;
			case 'tsPlacementTest':
				$sContent = self::getPlacementTest($tagAttributes['server'], $tagAttributes['key'], $tagAttributes['language'], $tagAttributes['currencyid'], $tagAttributes['currencyiso']);
				break;
			case 'tsRegistrationForm':
				$sContent = self::getRegistrationForm($tagAttributes['server'], $tagAttributes['key'], $tagAttributes['language']);
				break;
			case 'default':
				$sContent = self::getDefault($tagAttributes['server'], $tagAttributes['combinationkey'], $tagAttributes['templatekey']);
				break;
		}

		self::$content[$cacheKey] = $sContent;
		
		return $sContent;
	}

	/**
	 * @param string $sServer
	 * @param string $sKey
	 * @param string $sLanguage
	 * @return string
	 */
	private static function getTsFeedback($sServer, $sKey, $sLanguage) {

		$aSubmitVars['r'] = isset($_GET['r']) ? $_GET['r'] : '';
		$aSubmitVars['KEY'] = $sKey;
		$aSubmitVars['save'] = $_POST['save'];
		$aSubmitVars['sLanguage'] = $sLanguage;
		$aSubmitVars['pid'] = $_SESSION['__pid'];
		$aSubmitVars['pp'] = $_SESSION['__ppa'];

		if($_REQUEST['task'] == 'detail' and $_REQUEST['action'] == 'save') {
			foreach($_REQUEST as $key => $value) {
				$aSubmitVars[$key] = $value;
			}
		}

		$oSnoopy = new Snoopy();
		$oSnoopy->submit($sServer . '/system/extensions/kolumbus_feedback.php', $aSubmitVars);
		$sResults = $oSnoopy->results;

		return $sResults;
	}

	/**
	 * @param string $sServer
	 * @param string $sKey
	 * @param string $sLanguage define the language of the site, the default Language of the school is used if it is not defined
	 * @param string $iCurrencyId define the Currency of the Site by ID , otherwise the first currency or $_VARS['sCurrency'] is used
	 * @param string $sCurrencyIso define the Currency of the Site by ISO name, otherwise the first currency or $_VARS['idCurrency'] is used
	 * @return string
	 */
	private static function getPlacementTest($sServer, $sKey, $sLanguage = '', $iCurrencyId = '', $sCurrencyIso = '') {

		$aSubmitVars['r'] = $_REQUEST['r'];
		$aSubmitVars['KEY'] = $sKey;
		$aSubmitVars['save'] = $_POST['save'] ?? null;
		$aSubmitVars['isPeriod'] = $_POST['idPeriod'] ?? null;

		if($sLanguage !== '') {
			$aSubmitVars['page_language'] = $sLanguage;
		}
		if($iCurrencyId !== '') {
			$aSubmitVars['idCurrency'] = $iCurrencyId;
		}
		if($sCurrencyIso !== '') {
			$aSubmitVars['sCurrency'] = $sCurrencyIso;
		}

		$oSnoopy = new Snoopy();
		$oSnoopy->submit($sServer . '/system/extensions/kolumbus_placementtest.php', $aSubmitVars);
		$sResults = $oSnoopy->results;

		return $sResults;
	}

	/**
	 * @param string $sServer
	 * @param string $sKey
	 * @param string $sLanguage
	 * @return string
	 */
	private static function getRegistrationForm($sServer, $sKey = '', $sLanguage = '') {

		$aSubmitVars = array();

		if(!empty($_REQUEST)) {
			foreach((array)$_REQUEST as $mKey=>$mValue) {
				$aSubmitVars[$mKey] = $mValue;
			}
		}
		if($sKey !== '') {
			$aSubmitVars['form_key'] = $sKey;
		}
		if($sLanguage !== '') {
			$aSubmitVars['page_language'] = $sLanguage;
		}

		$oSnoopy = new Snoopy();
		$aFiles = array();

		if(!empty($_FILES)) {

			$sTempDir = sys_get_temp_dir();
			if(!is_writeable($sTempDir)) {
				die('Fatal error while uploading file');
			}

			foreach((array)$_FILES as $sKey => $mItems) {
				if(!is_array($mItems['name'])) {
					$sTarget = $sTempDir . '/' . $mItems['name'];
					if(move_uploaded_file($mItems['tmp_name'], $sTarget)) {
						$aFiles[$sKey] = $sTarget;
					}
				} else {
					self::prepareFiles($mItems['name'], $mItems['tmp_name'], $aFiles[$sKey], $sTempDir);
				}
			}

		}

		$oSnoopy->cookies = $_COOKIE;
		unset($aSubmitVars['PHPSESSID']);

		$oSnoopy->set_submit_multipart();
		$oSnoopy->submit($sServer . '/system/extensions/thebing_registration_form.php?'.$_SERVER['QUERY_STRING'], $aSubmitVars, $aFiles);
		$sResults = $oSnoopy->results;

		if(
			isset($_REQUEST['task']) &&
			(
				$_REQUEST['task'] == 'get_js' ||
				$_REQUEST['task'] == 'get_image' ||
				$_REQUEST['task'] == 'get_file' ||
				$_REQUEST['task'] == 'get_ajax'
			)
		) {
			foreach((array)$oSnoopy->headers as $sHeader) {
				if(strpos($sHeader, 'Content-Type') !== false) {
					header($sHeader);
				}
			}
			ob_clean();
			echo $oSnoopy->results;
			die();
		}

		// Make internal server error of registration form recognizably
		if($oSnoopy->status == 500) {
			$sResults = 'Fatal error of registration form!';
		}

		// If content is already sent, no cookies can be set afterwards.
		// That's deadly for the function of the registration form, so that's a fatal error.
		// Usually this is an user error!
		if(headers_sent()) {
			$sResults = 'Wrong order of content output. Check whether you\'ve no output before including of registration form!';
		}

		foreach((array)$oSnoopy->cookies as $sKey=>$mValue) {
			if(is_scalar($mValue)) {
				setcookie($sKey, $mValue);
			}
		}

		if(!empty($aFiles)) {
			self::unlinkFiles($aFiles);
		}

		return $sResults;
	}

	/**
	 * @param string $sServer
	 * @param string $sCombinationKey
	 * @param string $sTemplateKey
	 * @return string
	 */
	private static function getDefault($sServer, $sCombinationKey, $sTemplateKey) {

		$oSnippet = new Thebing_Snippet($sServer, $sCombinationKey, $sTemplateKey);
	
		$fideloParameter = get_query_var('fidelo_parameter');

		if(!empty($fideloParameter)) {
			foreach($fideloParameter as $key=>$value) {
				$oSnippet->setCombinationParameter($key, $value);
			}
		}
		
		$oSnippet->execute();
		$sContent = $oSnippet->getContent();

		self::$result = $oSnippet->getResult();

		if(
			!empty(self::$result['usage']) &&
			(
				self::$result['usage'] == 'course_list' ||
				self::$result['usage'] == 'course_details'
			)
		) {
			
			$coursePageUri = get_option('thebingsnippet_course_route');
			
			$regex = get_shortcode_regex(['course-link']);

			$countMatches = preg_match_all("/$regex/", $sContent, $matches);
			if($countMatches > 0) {
				foreach($matches[0] as $key=>$match) {
					$atts = shortcode_parse_atts( $matches[3][$key] );
					$sContent = str_replace($match, str_replace('{course_name}', $atts['slug'], $coursePageUri), $sContent);
				}
				
			}
		}
		
		return $sContent;
	}

	/**
	 * @param array $aFiles
	 */
	private static function unlinkFiles(&$aFiles) {

		foreach((array)$aFiles as $mKey => $mFile) {
			if(is_array($mFile)) {
				self::unlinkFiles($aFiles[$mKey]);
			} else if(is_file($mFile)) {
				unlink($mFile);
			}
		}

	}

	/**
	 * @param $mItems
	 * @param $mTmpItems
	 * @param $aFiles
	 * @param $sTempDir
	 */
	private static function prepareFiles($mItems, $mTmpItems, &$aFiles, $sTempDir) {

		foreach((array)$mItems as $sKey => $aItems) {
			if(!is_array($aItems)) {
				$sTarget = $sTempDir . '/' . $mItems[$sKey];
				if(move_uploaded_file($mTmpItems[$sKey], $sTarget)) {
					$aFiles[$sKey] = $sTarget;
				}
			} else {
				self::prepareFiles($mItems[$sKey], $mTmpItems[$sKey], $aFiles[$sKey], $sTempDir);
			}
		}

	}

	static public function thebingsnippet_generate_rewrite_rules($routing) {
		
		$coursePageUri = get_option('thebingsnippet_course_route');
		
		if(empty($coursePageUri)) {
			return;
		}
		
		$coursePageId = (int)get_option('thebingsnippet_course_page');

		$coursePage = get_post($coursePageId);
		
		$coursePageUri = str_replace('{course_name}', '([^/]+)', $coursePageUri);

		$additionalRules = [
			'^'.ltrim($coursePageUri, '/') => 'index.php?pagename='.$coursePage->post_name.'&fidelo_parameter[course_slug]=$matches[1]'
		];
		
		$routing->rules = $additionalRules + $routing->rules;
		
	}
	
	static public function initRouting() {
		
	}
	
	static public function thebingsnippet_settings_init() {

		// register a new setting for "thebingsnippet" page
		register_setting('thebingsnippet', 'thebingsnippet_url');
		register_setting('thebingsnippet', 'thebingsnippet_course_route');
		register_setting('thebingsnippet', 'thebingsnippet_course_page');

		// register a new section in the "thebingsnippet" page
		add_settings_section(
			'thebingsnippet_settings',
			__( 'Settings', 'thebingsnippet' ),
			'Thebing_Wordpress::thebingsnippet_section_settings',
			'thebingsnippet'
		);

		// register a new field in the "thebingsnippet_section_developers" section, inside the "thebingsnippet" page
		add_settings_field(
			'thebingsnippet_url', // as of WP 4.6 this value is used only internally
			// use $args' label_for to populate the id inside the callback
			__( 'URL', 'thebingsnippet' ),
			'Thebing_Wordpress::thebingsnippet_field_settings',
			'thebingsnippet',
			'thebingsnippet_settings'
		);

		// register a new field in the "thebingsnippet_section_developers" section, inside the "thebingsnippet" page
		add_settings_field(
			'thebingsnippet_course_route', // as of WP 4.6 this value is used only internally
			// use $args' label_for to populate the id inside the callback
			__( 'URI scheme for course detail pages', 'thebingsnippet' ),
			'Thebing_Wordpress::thebingsnippet_field_course_route',
			'thebingsnippet',
			'thebingsnippet_settings'
		);

		// register a new field in the "thebingsnippet_section_developers" section, inside the "thebingsnippet" page
		add_settings_field(
			'thebingsnippet_course_page', // as of WP 4.6 this value is used only internally
			// use $args' label_for to populate the id inside the callback
			__( 'Course detail page', 'thebingsnippet' ),
			'Thebing_Wordpress::thebingsnippet_field_pages',
			'thebingsnippet',
			'thebingsnippet_settings'
		);

	}

	// section callbacks can accept an $args parameter, which is an array.
	// $args have the following keys defined: title, id, callback.
	// the values are defined at the add_settings_section() function.
	static public function thebingsnippet_section_settings( $args ) {
	 ?>
	 <p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Please enter the URL to your Fidelo installation (e.g. https://example.fidelo.com).', 'thebingsnippet' ); ?></p>
	 <?php
	}

	// pill field cb

	// field callbacks can accept an $args parameter, which is an array.
	// $args is defined at the add_settings_field() function.
	// wordpress has magic interaction with the following keys: label_for, class.
	// the "label_for" key value is used for the "for" attribute of the <label>.
	// the "class" key value is used for the "class" attribute of the <tr> containing the field.
	// you can add custom key value pairs to be used inside your callbacks.
	static public function thebingsnippet_field_settings( $args ) {
		echo '<input name="thebingsnippet_url" id="thebingsnippet_url" type="input" value="'.esc_attr(get_option('thebingsnippet_url')).'"/>';
	}
	
	static public function thebingsnippet_field_course_route( $args ) {
		echo '<input name="thebingsnippet_course_route" id="thebingsnippet_course_route" type="input" value="'.esc_attr(get_option('thebingsnippet_course_route')).'"/>';
	}

	static public function thebingsnippet_field_pages($args) {

?>
		<select id="thebingsnippet_course_page" name="thebingsnippet_course_page">
		<?php
		if( $pages = get_pages() ){
			foreach( $pages as $page ){
				echo '<option value="' . $page->ID . '" ' . selected( $page->ID, get_option('thebingsnippet_course_page') ) . '>' . $page->post_title . '</option>';
			}
		}
		?>
		</select>
	<?php
	}
	
	/**
	 * top level menu
	 */
	static public function thebingsnippet_options_page() {

		// add top level menu page
		add_menu_page(
			'Fidelo',
			'Fidelo Snippet',
			'manage_options',
			'thebingsnippet',
			'Thebing_Wordpress::thebingsnippet_options_page_html'
		);

	}
	
	/**
	 * top level menu:
	 * callback functions
	 */
	static public function thebingsnippet_options_page_html() {
		
		// check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// check if the user have submitted the settings
		// wordpress will add the "settings-updated" $_GET parameter to the url
		if(isset($_GET['settings-updated'])) {

			$sUrl = get_option('thebingsnippet_url');

			$sRoot = get_home_path();

			$sProxyFile = file_get_contents(__DIR__.'/../thebing_proxy.php');

			$sProxyFile = str_replace('{FIDELO_URL}', $sUrl, $sProxyFile);

			file_put_contents($sRoot.'thebing_proxy.php', $sProxyFile);

			$sTestUrl = $sUrl.'/system/extensions/tc_api.php?task=check_installation';
			
			// Fallback, file_get_contents darf vielleicht keine URLs öffnen
			if(function_exists('curl_init')) {
				$rCurl = curl_init($sTestUrl);
				curl_setopt($rCurl, CURLOPT_RETURNTRANSFER, true);
				$sTest = curl_exec($rCurl);
				curl_close($rCurl);
			} else {
				$sTest = file_get_contents($sTestUrl);
			}

			// Valid Fidelo installation
			if($sTest !== 'ok') {
				add_settings_error( 'thebingsnippet_messages', 'thebingsnippet_message', __( 'No valid Fidelo installation!', 'thebingsnippet' ), 'error' );
			}

			add_settings_error( 'thebingsnippet_messages', 'thebingsnippet_message', __( 'Settings saved and proxy file generated!', 'thebingsnippet' ), 'updated' );

			flush_rewrite_rules();
			
		}

	 // show error/update messages
	 settings_errors( 'thebingsnippet_messages' );
	 ?>
	 <div class="wrap">
	 <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
	 <form action="options.php" method="post">
	 <?php
	 // output security fields for the registered setting "thebingsnippet"
	 settings_fields('thebingsnippet');
	 // output setting sections and their fields
	 // (sections are registered for "thebingsnippet", each field is registered to a specific section)
	 do_settings_sections('thebingsnippet');
	 // output save settings button
	 submit_button('Save settings and generate proxy script');
	 ?>
	 </form>
	 </div>
	 <?php
	}
	
	static public function thebingsnippet_query_vars( $query_vars ) {
	   $query_vars[] = 'fidelo_parameter';
	   return $query_vars;
	}
	
	static public function thebingsnippet_change_page_title ($title_parts ) {
		global $post;

		$coursePageId = (int)get_option('thebingsnippet_course_page');
		
		if(
			$post instanceof WP_Post &&
			$post->ID == $coursePageId
		) {
			if (empty(self::$result)) {
				self::checkSnippet($post);
			}

			if(!empty(self::$result['title'])) {
				$title_parts['title'] = self::$result['title'];
			}
		}
		
		return $title_parts;
	}

	static public function checkSnippet($post) {
		
		$fideloParameter = get_query_var('fidelo_parameter');
		
		if (
			!empty($fideloParameter) && 
			preg_match( '#\[fidelo-snippet.*\]#', $post->post_content, $matches ) === 1
		) {
			do_shortcode( $matches[0]);			
		}
		
	}
		
	static public function thebingsnippet_the_title ($title, $id=null) {
		global $post;

		$coursePageId = (int)get_option('thebingsnippet_course_page');
		
		if(
			!empty($id) &&
			$id == $coursePageId
		) {
			if (empty(self::$result)) {
				self::checkSnippet($post);
			}

			if(!empty(self::$result['title'])) {
				$title = self::$result['title'];
			}
		}
		
		return $title;
	}

	/**
	 * Yoast Titel für Kursdetailseite deaktivieren
	 * 
	 * @global WP_Post $post
	 * @param string $title
	 * @return boolean
	 */
	static public function thebingsnippet_yoast_title($title) {
		global $post;
		
		$coursePageId = (int)get_option('thebingsnippet_course_page');
		
		if(
			$post instanceof WP_Post &&
			$post->ID == $coursePageId
		) {
			return false;
		}
		
		return $title;
	}
	
}