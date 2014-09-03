(function () {
	var DEBUG = true;

	function clog(x){
		if(DEBUG)
			console.log(x);
	}

	function keyup(ev)
	{
		clog( $(ev.target).val() );
		var data = getData(ev.target);
		if(data.toFind && !data.noLink ) handleURL(ev.target);
	}

	function getData(el)
	{
		return 	$(el).data();
	}

	function saveData(el, data)
	{
		$(el).data(data);
	}

	function find_urls(text)
	{
		geturl = new RegExp("(^|[ \t\r\n])((ftp|http|https|gopher|mailto|news|nntp|telnet|wais|file|prospero|aim|webcal):(([A-Za-z0-9$_.+!*(),;/?:@&~=-])|%[A-Fa-f0-9]{2}){2,}(#([a-zA-Z0-9][a-zA-Z0-9$_.+!*(),;/?:@&~=%-]*))?([A-Za-z0-9$_+!*();/?:~-]))","g");
		var s = text.match(geturl);
		return s;
	}

	function isValidURL(url)
	{
		var RegExp = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/;

		if(RegExp.test(url)) return true;
		return false;
	}

	

	function reset_infoUrl(el)
	{
		clog('reset')
		$(el).parent().find('.atc_title, .atc_desc').html('');
		$(el).parent().find('.atc_active').attr('src', '');
		$(el).parent().find('.atc_title').parent().find().attr('href', '');
		$(el).parent().find('.attach_content').addClass('hide');
	}

	function handleURL(el)
	{
		var text = $(el).val();
		if(text == '') return;
		var url = find_urls( text );
		if (url)
		{
			if( isValidURL(url) )
			{
				var data = getData(el);
				var $parent = $(el).parent();
				$parent.find('.loader, .atc_loading').show();
				data.toFind = false;
				$.post('/ajax.php', {
					data: { url: encodeURIComponent(url) }
				}, function(r){
					if(r.success == true){			
						clog(r.data);
						var response = r.data;
						//Set Content
							if(response.title){
								$parent.find('.atc_title').html(response.title);
								$parent.find('.atc_title').parent().attr('href', url);
							}
							if(response.description)
								$parent.find('.atc_desc').html(response.description);
								$parent.find('.atc_price').html(response.price);
							if(response.video)
								$parent.find('.atc_title').attr('data-video', response.video);


							$parent.find('.atc_images, .atc_total_image_nav, .atc_total_images_info').hide();

							if(response.total_images > 0)
							{
								$parent.find('.atc_total_images').html(response.total_images);

								$parent.find('.atc_images').html(' ');
								$.each(response.images, function (a, b)
								{
									$parent.find('.atc_images').append('<img src="'+b+'" width="100" class="'+(a+1)+'">');
								});
								$parent.find('.atc_images img').hide();
								//Show first image
								$parent.find('img.1').fadeIn().addClass('atc_active');
								$parent.find('.cur_image').val(1);
								$parent.find('.cur_image_num').html(1);

								// next image
								$parent.find('.next').unbind('click');
								$parent.find('.next').bind("click", function(){
									var total_images = parseInt($parent.find('.atc_total_images').html());
									clog('tota'+total_images);
									if (total_images > 0)
									{
										var index = parseInt( $parent.find('.atc_active').attr('class') );
										clog('i:'+index);
										$parent.find('.atc_active').removeClass('atc_active');
										$parent.find('img.'+index).hide();
										if(index < total_images){
											new_index = index+1; 
										} else { 
											new_index = 1;
										}
										clog(new_index);
										$parent.find('.cur_image').val(new_index);
										$parent.find('.cur_image_num').html(new_index);
										$parent.find('img.'+new_index).show().addClass('atc_active');
									}
									return false;
								});
					 
								// prev image
								$parent.find('#prev').unbind('click');
								$parent.find('.prev').bind("click", function(){				 
									var total_images = parseInt($parent.find('.atc_total_images').html());
									clog('tota'+total_images);
									if (total_images > 0)
									{
										var index = parseInt( $parent.find('.atc_active').attr('id') );
										clog('i:'+index);
										$parent.find('.atc_active').removeClass('atc_active');
										$parent.find('img.'+index).hide();
										if(index > 1){
											new_index = index-1; 
										} else { 
											new_index = total_images;
										}
										clog(new_index);
										$parent.find('.cur_image').val(new_index);
										$parent.find('.cur_image_num').html(new_index);
										$parent.find('img.'+new_index).show().addClass('atc_active');
									}
									return false;
								});
								$parent.find('.atc_images, .atc_total_image_nav, .atc_total_images_info').show();
							}

							//Flip Viewable Content
							$parent.find('.attach_content').removeClass('hide').fadeIn('slow');
							$parent.find('.atc_loading').hide();

						
							return response;
					} else {
						$parent.find('.loader').hide();
						toFind = false;
					}
				}, 'json');
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	function delegate(data) {
		var el = $('.parse_post');
		$(document).on('keyup','.parse_post', keyup );
		
		$(document).on('paste', '.parse_post', function(){
			if(data.toFind && !data.noLink) handleURL( $(this) );
			var element = this;
			var ell = $(this);
			setTimeout(function () {
			    var text = $(element).val();
				if(data.toFind) handleURL(ell);
			    }, 100);
		});
		
		$(el).click(function(e){
			e.preventDefault();
			return false;
		});

		$(el).focus(function(){
			var data = $(this).data();
			$(this).attr('rows', 3);
			if(data.toFind && !data.noLink){
				handleURL(el);
			}
			return false;
		}).focusout(function(){	        	
			if( $(this).val() == "" ) {
				$(this).attr('rows', 1);
			}
		});

		$(el).parent().find('.rm_link').bind('click', function(){
			clog('rm');
			var data = getData(el);
			data.toFind = false;
			data.noLink = true;
			$(el).parent().find('.attach_content').addClass('hide');
			$(el).bind('input', function(){
				if( $(this).val() == ''){
					data.toFind = true;
					data.noLink = false;
					saveData(el, data);
					reset_infoUrl(el);
					$(this).unbind('input');
				}
			});
		});
	}
	
	function init(arg) 
	{
		/*
		$(arg).data({
			'toFind' : true,
			'noLink' : false
		});
		*/
		console.log('dkasljhflka');
		delegate({
			'toFind' : true,
			'noLink' : false
		});
	}

	window.parse_url= {
		init: init,
		isValidURL: isValidURL,
		reset_infoUrl: reset_infoUrl
	};
	$(init);
})();
