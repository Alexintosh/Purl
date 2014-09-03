<?php
class Utils {

	static function pre($out, $opt = 0)
	{
		$html = '
			<div class="_utils_pre" style="border:1px solid #000;margin:1px 0">
				<h2>Utils::pre</h2>
				<p>
					Count: '.count($out).'
				</p>

				<a href="#">Toggle <b>PRINT_R</b></a>
				<pre>'.strip_tags(print_r($out, 1)).'</pre>
			</div>

			<script src="/js/jquery-2.0.3.min.js"></script>
			<script>
			(function() {
			function init() {
				if(typeof __utils_pre != "undefined")
					return;
				window["__utils_pre"] = 1;

				$("._utils_pre a").on("click", function() {
					$(this).next().slideToggle();
					return false;
				});
			}

			$(init);
			})();
			</script>
		';

		$html = str_replace('<img', '<_img', $html); // Remove images

		if($opt == 1)
			return $html;
		echo $html;
		if($opt == 2)
			die;
	}

	/* XXX clear output buffer? */
	static private function jret($state, $data = null) 
	{
		@ob_clean();
		die(json_encode(array(
			'success' => $state,
			'data' => $data
		)));
	}

	static function jOk($data = NULL)
	{
		self::jret(true, $data);
	}

	static function jFalse($data = NULL)
	{
		self::jret(false, $data);
	}
	static public function parse_url($url)
	{
		require_once('lib/ganon.php');
		//if html 
		//else forse è un immagine?
		if( $img = @getimagesize($url) ){
			$return_array['type'] = 'image'; 
			$return_array['images'] = array($url);
			$return_array['total_images'] = count($return_array['images']); 
			Utils::jOk($return_array);
		}

		$html = @file_get_dom($url);
		if( !$html ) Utils::jFalse();
		$return_array['title'] = @$html('title',0)->getPlainText();
		$return_array['description'] = @$html('meta[name=description]', 0)->content;
		$metaimg = @$html('meta[name=thumbnail]', 0)->content;
		$metaSiteName= @$html('meta[property="og:site_name"]', 0)->content;
		$metafbimg = @$html('meta[property="og:image"]', 0)->content;
		$metafbvideo = @$html('meta[property="og:video"]', 0)->content;
		$metaType= @$html('meta[property="og:type"]', 0)->content;
		//og:image per video youtube e og:video per ipfram embed
		//controllare og:type per sapere se è un video
		if($metaimg){
			$return_array['images'] = array($metaimg);
			$return_array['total_images'] = count($return_array['images']); 
			Utils::jOk($return_array);
			die;
		} elseif ($metafbimg && (strtolower($metaSiteName) != 'youtube')){
			$return_array['images'] = array($metafbimg);
			$return_array['total_images'] = count($return_array['images']); 
			Utils::jOk($return_array);
			die;
		} elseif ($metafbimg && (strtolower($metaSiteName) == 'youtube') ){
			$return_array['images'] = array($metafbimg);
			$return_array['type'] = 'video'; 
			$return_array['video'] = ( $metafbvideo ) ? $metafbvideo : 'video';
			$return_array['total_images'] = count($return_array['images']); 
			Utils::jOk($return_array);
			die;
		}
		// Parse Images
		$images_array = self::extract_tags( $html, 'img' );
		if( count($images_array) < 1 ) Utils::jOk($return_array);
		$images = array();
		for ($i=0;$i<=sizeof($images_array);$i++)
		{
			$img = trim(@$images_array[$i]['attributes']['src']);
			$details = @getimagesize($img);
			
			if(is_array($details))
			{
				list($width, $height, $type, $attr) = $details;
				$width = intval($width);
				$height = intval($height);
				if( $width < 120 ) continue;
				else $images[] = $img;
				if( count($images) > 4) break;
			} 
		}

		$return_array['images'] = $images;
		$return_array['total_images'] = count($return_array['images']); 
		
		Utils::jOk($return_array);
	}

	static public function extract_tags( $html, $tag, $selfclosing = null, $return_the_entire_tag = false, $charset = 'ISO-8859-1' )
	{
	 
		if ( is_array($tag) ){
			$tag = implode('|', $tag);
		}
/*
 * 	 
 */
		//If the user didn't specify if $tag is a self-closing tag we try to auto-detect it
		//by checking against a list of known self-closing tags.
		$selfclosing_tags = array( 'area', 'base', 'basefont', 'br', 'hr', 'input', 'img', 'link', 'meta', 'col', 'param' );
		if ( is_null($selfclosing) ){
			$selfclosing = in_array( $tag, $selfclosing_tags );
		}
	 
		//The regexp is different for normal and self-closing tags because I can't figure out 
		//how to make a sufficiently robust unified one.
		if ( $selfclosing ){
			$tag_pattern = 
				'@<(?P<tag>'.$tag.')			# <tag
				(?P<attributes>\s[^>]+)?		# attributes, if any
				\s*/?>					# /> or just >, being lenient here 
				@xsi';
		} else {
			$tag_pattern = 
				'@<(?P<tag>'.$tag.')			# <tag
				(?P<attributes>\s[^>]+)?		# attributes, if any
				\s*>					# >
				(?P<contents>.*?)			# tag contents
				</(?P=tag)>				# the closing </tag>
				@xsi';
		}
	 
		$attribute_pattern = 
			'@
			(?P<name>\w+)							# attribute name
			\s*=\s*
			(
				(?P<quote>[\"\'])(?P<value_quoted>.*?)(?P=quote)	# a quoted value
				|							# or
				(?P<value_unquoted>[^\s"\']+?)(?:\s+|$)			# an unquoted value (terminated by whitespace or EOF) 
			)
			@xsi';
	 
		//Find all tags 
		if ( !preg_match_all($tag_pattern, $html, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) ){
			//Return an empty array if we didn't find anything
			return array();
		}
	 
		$tags = array();
		foreach ($matches as $match){
	 
			//Parse tag attributes, if any
			$attributes = array();
			if ( !empty($match['attributes'][0]) ){ 
	 
				if ( preg_match_all( $attribute_pattern, $match['attributes'][0], $attribute_data, PREG_SET_ORDER ) ){
					//Turn the attribute data into a name->value array
					foreach($attribute_data as $attr){
						if( !empty($attr['value_quoted']) ){
							$value = $attr['value_quoted'];
						} else if( !empty($attr['value_unquoted']) ){
							$value = $attr['value_unquoted'];
						} else {
							$value = '';
						}
	 
						//Passing the value through html_entity_decode is handy when you want
						//to extract link URLs or something like that. You might want to remove
						//or modify this call if it doesn't fit your situation.
						$value = html_entity_decode( $value, ENT_QUOTES, $charset );
	 
						$attributes[$attr['name']] = $value;
					}
				}
	 
			}
	 
			$tag = array(
				'tag_name' => $match['tag'][0],
				'offset' => $match[0][1], 
				'contents' => !empty($match['contents'])?$match['contents'][0]:'', //empty for self-closing tags
				'attributes' => $attributes, 
			);
			if ( $return_the_entire_tag ){
				$tag['full_tag'] = $match[0][0]; 			
			}
	 
			$tags[] = $tag;
		}
	 
		return $tags;
	}
}
?>
