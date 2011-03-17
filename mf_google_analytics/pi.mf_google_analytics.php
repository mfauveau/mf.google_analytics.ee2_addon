<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

$plugin_info = array(
	'pi_name' => 'MF Google Analytics',
	'pi_version' => '0.1',
	'pi_author' => 'Matthieu Fauveau',
	'pi_author_url' => 'https://github.com/mfauveau/mf.google_analytics.ee2_addon',
	'pi_description' => 'This plugin adds the Google Analytics tracking code in templates.',
	'pi_usage' => Mf_google_analytics::usage()
);

/**
 * MF Google Analytics
 *
 * This plugin adds the Google Analytics tracking code in templates. This plugin is not associated in any way with Google.
 * It's originally inspired by Nicolas Bottari's work at http://nicolasbottari.com/expressionengine_cms/google_analytics/.
 *
 * @package  ExpressionEngine
 * @category Plugins
 * @author  Matthieu Fauveau
 * @license  Noncommercial-Share Alike 3.0 Unported License http://creativecommons.org/licenses/by-nc-sa/3.0/
 */
class Mf_google_analytics
{
	var $return_data = '';
	
	var $_ga_js = '(function() {
	var ga = document.createElement("script"); ga.type = "text/javascript"; ga.async = true;
	ga.src = ("https:" == document.location.protocol ? "https://ssl" : "http://www") + ".google-analytics.com/ga.js";
	var s = document.getElementsByTagName("script")[0]; s.parentNode.insertBefore(ga, s);
})();';

	/**
	 * Constructor
	 *
	 */
	function Mf_google_analytics()
	{
		$this->EE =& get_instance();

		$account = ($this->EE->TMPL->fetch_param('account') !== FALSE) ? $this->EE->TMPL->fetch_param('account') : '';
		$one_push = ($this->EE->TMPL->fetch_param('one_push') !== FALSE) ? $this->EE->TMPL->fetch_param('one_push') : 'yes';
		$allow_hash = ($this->EE->TMPL->fetch_param('allow_hash') !== FALSE) ? $this->EE->TMPL->fetch_param('allow_hash') : 'yes';
		$allow_linker = ($this->EE->TMPL->fetch_param('allow_linker') !== FALSE) ? $this->EE->TMPL->fetch_param('allow_linker') : 'no';		
		$cookie_path = ($this->EE->TMPL->fetch_param('cookie_path') !== FALSE) ? $this->EE->TMPL->fetch_param('cookie_path') : '/';
		$domain_name = ($this->EE->TMPL->fetch_param('domain_name') !== FALSE) ? $this->EE->TMPL->fetch_param('domain_name') : 'auto';		
		$site_search = ($this->EE->TMPL->fetch_param('site_search') !== FALSE) ? $this->EE->TMPL->fetch_param('site_search') : 'no';
		$site_search_query_parameter = ($this->EE->TMPL->fetch_param('site_search_query_parameter') !== FALSE) ? $this->EE->TMPL->fetch_param('site_search_query_parameter') : '/'. $this->EE->uri->segment(1) . '?q=';
		$split = ($this->EE->TMPL->fetch_param('split') !== FALSE) ? $this->EE->TMPL->fetch_param('split') : 'no';
		
		if ($account == '') {
			return '';
		}

		$methods = array();
		
		$methods['setAccount'] = $account;
		$methods['setAllowHash'] = ($allow_hash === 'yes') ? 'true' : 'false';
		$methods['setAllowLinker'] = ($allow_linker === 'yes') ? 'true' : 'false';
		$methods['setCookiePath'] = $cookie_path;
		$methods['setDomainName'] = $domain_name;
		$methods['trackPageView'] = '/' . $this->EE->uri->uri_string;
		
		if ($methods['trackPageView'] === '/') {
			$methods['trackPageView'] = '';
		}
		
		if ($methods['setCookiePath'] === '/') {
			unset($methods['setCookiePath']);
		}
			
		if ($site_search === 'yes' and strlen($this->EE->uri->query_string) >= 32) {
			$search_id = substr($this->EE->uri->query_string, 0, 32);

			$query = $this->EE->db->query("SELECT keywords FROM exp_search WHERE search_id = '".$this->EE->db->escape_str($search_id)."'");
			if ($query->num_rows() == 1) {
				$keywords = strtolower(str_replace(' ', '+', $query->row('keywords')));
				
				$methods['trackPageView'] = $site_search_query_parameter . $keywords;
			}			
		}

		$this->return_data = '<script type="text/javascript">
var _gaq = _gaq || [];
';
		
		foreach ($methods as $key => $val) {			
			$command = "['_". $key ."'";
			
			if ($val === '') {
				$command .= "]";
			}
			else {
				if ($val !== 'true' and $val !== 'false') {
					$val = "'". $val ."'";
				}
			
				$command .= ", ". $val ."]";
			}
		
			if ($one_push === 'yes') {
				$commands[] = $command;
			}
			else {									
				$commands[] = "_gaq.push(". $command .");";
			}
		}
		
		if ($one_push === 'yes') {
			$this->return_data .= "_gaq.push(\n\t". implode(",\n\t", $commands) ."\n);";
		}
		else {
			$this->return_data .= implode("\n", $commands);
		}
			
		if ($split === 'no') {
			$this->return_data .= $this->_ga_js;
		}
		
		$this->return_data .= '
</script>
';

		return $this->return_data;
	}
	
	// --------------------------------------------------------------------

	/**
	 * Ga js
	 *
	 * Return the javascript for ga.js
	 *
	 * @access public
	 * @return string
	 */
	function ga_js()
	{
		return '<script type="text/javascript">
'. $this->_ga_js .'
</script>
';
	}

	// --------------------------------------------------------------------

	/**
	 * Track page view
	 *
	 * Return the javascript for trackPageView
	 *
	 * @access public
	 * @return string
	 */
	function track_page_view()
	{
		if ($this->EE->TMPL->fetch_param('target_url') !== FALSE) {
			$params[] = "'". $this->EE->TMPL->fetch_param('target_url') ."'";
			
			return "_gaq.push(['_trackPageView', ". implode(", ", $params) ."]); return false;";
		}
	}
	
	// --------------------------------------------------------------------

	/**
	 * Link
	 *
	 * Return the javascript for _link()
	 *
	 * @access public
	 * @return string
	 */
	function link()
	{
		if ($this->EE->TMPL->fetch_param('target_url') !== FALSE) {
			$params[] = "'". $this->EE->TMPL->fetch_param('target_url') ."'";
		
			if ($this->EE->TMPL->fetch_param('use_hash') !== FALSE) {
				if ($this->EE->TMPL->fetch_param('use_hash') === 'yes') {
					$params[] = 'true';
				}
			}
			
			return "_gaq.push(['_link', ". implode(", ", $params) ."]); return false;";
		}
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Link by POST
	 *
	 * Return the javascript for _link()
	 *
	 * @access public
	 * @return string
	 */
	function link_by_post()
	{
		$params[] = 'this';
		
		if ($this->EE->TMPL->fetch_param('use_hash') !== FALSE) {
			if ($this->EE->TMPL->fetch_param('use_hash') === 'yes') {
				$params[] = 'true';
			}
		}
			
		return "_gaq.push(['_linkByPost', ". implode(", ", $params) ."]); return false;";
	}

	// --------------------------------------------------------------------

	/**
	 * Usage
	 *
	 * Plugin Usage
	 *
	 * @access public
	 * @return string
	 */
	function usage()
	{
		ob_start();
?>
		Changelog
		********************************************************************
		
		Version 0.1
		
		This is inspired by my work converting Nicolas Bottari's plugin.
		This version correct a few bugs and support only asynchronous tracking.		



		Tracking Code
		********************************************************************

		------------------
		EXAMPLE USAGE:
		------------------

		{exp:mf_google_analytics account="UA-XXXXXXX-X"}

		------------------
		PARAMETERS:
		------------------

		account="UA-XXXXXXX-X"
		- The Google Analytics ID for your site. This is required.
		
		one_push="y"
		- Instead of having _gaq.push(...) for each call, all of your commands can be pushed at once. By default, this value is set to "y". Set it to "n" to disable this behavior.

		allow_hash="y"
		- Sets the allow domain hash flag. By default, this value is set to "y". Set it to "n" to disable this behavior.
		  The domain hashing functionality in Google Analytics creates a hash value from your domain, and uses this number to check cookie integrity for visitors. 
		  If you have multiple sub-domains, such as example1.example.com and example2.example.com, and you want to track user behavior across both of these sub-domains,
		  you would turn off domain hashing so that the cookie integrity check will not reject a user cookie coming from one domain to another. 
		  Additionally, you can turn this feature off to optimize per-page tracking performance.
		
		allow_linker="n"
		- Sets the linker functionality flag as part of enabling cross-domain user tracking. By default, this method is set to "n" and linking is disabled.
		  Set it to "y" to enable it. This should only be used if you track across multiple domains and sub-domains. When used, you should use the link and linkbypost methods.
		
		cookie_path="/myBlogDirectory/"
		- Sets the new cookie path for your site. By default this is not used, Google Analytics automatically set this to "/".
		
		domain_name=".mysite.com"
		- Sets the domain name for cookies. There are three modes to this method : "auto", "none", "[[]domain]". By default, the method is set to "auto", which attempts to resolve the domain name based on the location object in the DOM.

		site_search="y"
		- Enable the Site Search functionnality of Google Analytics. By default, this value is set to "n". 
		  If set to "y", the plugin will track Site Search queries on search result pages (i.e. search result pages where the last segment is the EE search id).
		  
		site_search_query_parameter="/search/results?q="
		- Sets the Site Search Query Parameter set in Google Analytics for the Site Search functionnality.
		  If site_search is set to "y" the default value is "/{segment_1}?q=".

		split="n"
		- Enable splitting of the code of the tracking code
		  If you prefer to put the Analytics snippet at the bottom of the page, you should know that you don't have to put the whole snippet at the bottom.
		  You can still keep most of the benefits of asynchronous loading by splitting the snippet in half—keep the first half at the top of the page and 
		  move the rest to the bottom. 
		  Because the first part of the tracking snippet has little to no afect on page rendering, you can leave that part at the top and put the part of 
		  the snippet that inserts ga.js at the bottom.
		  By default, the method is set to "n". If you set it you need to output the ga.js part by using {exp:mf_google_analytics:ga_js} just before the </body> tag.



		Track page view function
		********************************************************************
		
		This function act as an helper to push trackPageView. You can use it to track mailto links or file download.
		
		------------------
		EXAMPLE USAGE:
		------------------

		<a href="http://example.com/test.html" onclick="{exp:mf_google_analytics:track_page_view target_url='/downloads/file.zip'}">click me</a>
				
		------------------
		PARAMETERS:
		------------------
		
		target_url="/downloads/file.zip"
		- The mailto/file/outbound link to track.



		Link function
		********************************************************************
		
		This function works in conjunction with the domain_name and allow_linker methods to enable cross-domain user tracking.
		It will passes the cookies from this site to another via URL parameters (HTTP GET). It also changes the document.location and redirects the user to the new URL.
		allow_linker must be set to "y" in tracking code for this to work properly.
		
		------------------
		EXAMPLE USAGE:
		------------------

		<a href="http://example.com/test.html" onclick="{exp:mf_google_analytics:link target_url='http://example.com/test.html'}">click me</a>
				
		------------------
		PARAMETERS:
		------------------
		
		target_url="http://example.com/test.html"
		
		use_hash="y"
		- Set to "y" for passing tracking code variables by using the # anchortag separator rather than the default ? query string separator. By default, the method is set to "n".
		
		
		
		Link by post function
		********************************************************************
		
		This function works in conjunction with the domain_name and allow_linker methods to enable cross-domain user tracking.
		It will passes the cookies from the referring form to another site in a string appended to the action value of the form (HTTP POST). This method is typically used when 
		tracking user behavior from one site to a 3rd-party shopping cart site, but can also be used to send cookie data to other domains in pop-ups or in iFrames.	
		allow_linker must be set to "y" in tracking code for this to work properly.	
		
		------------------
		EXAMPLE USAGE:
		------------------

		<form action="http://www.shoppingcartsite.com/myService/formProcessor.php" name="f" method="post" onsubmit="{exp:mf_google_analytics:link_by_post}">
		...
		</form>
			
		------------------
		PARAMETERS:
		------------------
		
		use_hash="y"
		 -Set to "y" for passing tracking code variables by using the # anchortag separator rather than the default ? query string separator. By default, the method is set to "n".
				
		
  		<?php
		$buffer = ob_get_contents();

		ob_end_clean();

		return $buffer;
	}
	// --------------------------------------------------------------------

}
// END Mf_google_analytics Class

/* End of file pi.mf_google_analytics.php */
/* Location: ./system/expressionengine/third_party/mf_google_analytics/pi.mf_google_analytics.php */