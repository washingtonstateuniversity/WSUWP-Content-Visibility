/* global postL10n, customPostL10n */
( function( $, window ) {
	var updateVisibility, localizeText, updateText;
	window.checkedCustomGroups = [];
	var stamp = $( "#timestamp" ).html();
	var visibility = $( "#post-custom-visibility-display" ).html();
	var $postVisibilitySelect = $( "#post-custom-visibility-select" );
	var $timestampdiv = $( "#timestampdiv" );

	/**
	 * Localize text depending on its custom status. Default post status selections
	 * will be provided through the postL10n object. Our custom status selections
	 * through customPostL10n.
	 *
	 * @param text
	 * @returns {*}
	 */
	localizeText = function( text ) {
		if ( postL10n[ text ] ) {
			return postL10n[ text ];
		}

		return customPostL10n[ text ];
	};

	updateVisibility = function() {
		if ( "public" !== $postVisibilitySelect.find( "input:radio:checked" ).val() ) {
			$( "#stick" ).prop( "checked", false );
			$( "#sticky-span" ).hide();
		} else {
			$( "#sticky-span" ).show();
		}

		if ( "password" !== $postVisibilitySelect.find( "input:radio:checked" ).val() ) {
			$( "#password-span" ).hide();
		} else {
			$( "#password-span" ).show();
		}
	};

	updateText = function() {

		if ( !$timestampdiv.length ) {
			return true;
		}

		var attemptedDate, originalDate, currentDate, publishOn, postStatus = $( "#post_status" ),
			optPublish = $( 'option[value="publish"]', postStatus ), aa = $( "#aa" ).val(),
			mm = $( "#mm" ).val(), jj = $( "#jj" ).val(), hh = $( "#hh" ).val(), mn = $( "#mn" ).val();

		attemptedDate = new Date( aa, mm - 1, jj, hh, mn );
		originalDate = new Date( $( "#hidden_aa" ).val(), $( "#hidden_mm" ).val() - 1, $( "#hidden_jj" ).val(), $( "#hidden_hh" ).val(), $( "#hidden_mn" ).val() );
		currentDate = new Date( $( "#cur_aa" ).val(), $( "#cur_mm" ).val() - 1, $( "#cur_jj" ).val(), $( "#cur_hh" ).val(), $( "#cur_mn" ).val() );

		if ( attemptedDate.getFullYear() !== aa || ( 1 + attemptedDate.getMonth() ) !== mm || attemptedDate.getDate() !== jj || attemptedDate.getMinutes() !== mn ) {
			$timestampdiv.find( ".timestamp-wrap" ).addClass( "form-invalid" );
			return false;
		} else {
			$timestampdiv.find( ".timestamp-wrap" ).removeClass( "form-invalid" );
		}

		if ( attemptedDate > currentDate && "future" !== $( "#original_post_status" ).val() ) {
			publishOn = postL10n.publishOnFuture;
			$( "#publish" ).val( postL10n.schedule );
		} else if ( attemptedDate <= currentDate && "publish" !== $( "#original_post_status" ).val() ) {
			publishOn = postL10n.publishOn;
			$( "#publish" ).val( postL10n.publish );
		} else {
			publishOn = postL10n.publishOnPast;
			$( "#publish" ).val( postL10n.update );
		}
		if ( originalDate.toUTCString() === attemptedDate.toUTCString() ) { //Hack
			$( "#timestamp" ).html( stamp );
		} else {
			$( "#timestamp" ).html(
				"\n" + publishOn + " <b>" +
				postL10n.dateFormat
					.replace( "%1$s", $( 'option[value="' + mm + '"]', "#mm" ).attr( "data-text" ) )
					.replace( "%2$s", parseInt( jj, 10 ) )
					.replace( "%3$s", aa )
					.replace( "%4$s", ( "00" + hh ).slice( -2 ) )
					.replace( "%5$s", ( "00" + mn ).slice( -2 ) ) +
				"</b> "
			);
		}

		if ( "private" === $postVisibilitySelect.find( "input:radio:checked" ).val() ) {
			$( "#publish" ).val( postL10n.update );
			if ( 0 === optPublish.length ) {
				postStatus.append( '<option value="publish">' + postL10n.privatelyPublished + "</option>" );
			} else {
				optPublish.html( postL10n.privatelyPublished );
			}
			$( 'option[value="publish"]', postStatus ).prop( "selected", true );
			$( "#misc-publishing-actions .edit-post-status" ).hide();
		} else {
			if ( "future" === $( "#original_post_status" ).val() || "draft" === $( "#original_post_status" ).val() ) {
				if ( optPublish.length ) {
					optPublish.remove();
					postStatus.val( $( "#hidden_post_status" ).val() );
				}
			} else {
				optPublish.html( postL10n.published );
			}
			if ( postStatus.is( ":hidden" ) ) {
				$( "#misc-publishing-actions .edit-post-status" ).show();
			}
		}
		$( "#post-status-display" ).html( $( "option:selected", postStatus ).text() );
		if ( "private" === $( "option:selected", postStatus ).val() || "publish" === $( "option:selected", postStatus ).val() ) {
			$( "#save-post" ).hide();
		} else {
			$( "#save-post" ).show();
			if ( "pending" === $( "option:selected", postStatus ).val() ) {
				$( "#save-post" ).show().val( postL10n.savePending );
			} else {
				$( "#save-post" ).show().val( postL10n.saveDraft );
			}
		}
		return true;
	};

	$( document ).ready( function() {
		/**
		 * When "Edit" is clicked next to custom visibility, show the available options.
		 */
		$( "#custom-visibility .edit-custom-visibility" ).click( function( e ) {
			e.preventDefault();

			if ( $postVisibilitySelect.is( ":hidden" ) ) {
				updateVisibility();
				$postVisibilitySelect.slideDown( "fast", function() {
					$postVisibilitySelect.find( 'input[type="radio"]' ).first().focus();
				} );
			}
			$( this ).hide();
		} );

		$( "#custom-visibility-radio-custom" ).click( function() {
			$( ".remove-custom-visibility" ).each( function( x, el ) { $( el ).prop( "checked", false ); } );
			$( "#hidden-custom-post-visibility" ).val( "public" );
			if ( 0 < Object.keys( window.checkedCustomGroups ).length ) {
				$( ".custom-visibility-groups input[type='checkbox']" ).each( function( x, el ) {
					if ( window.checkedCustomGroups.hasOwnProperty( $( el ).attr( "name" ) ) ) {
						$( el ).prop( "checked", true );
					} else {
						$( el ).prop( "checked", false );
					}
				} );
			}
			$( ".custom-visibility-groups" ).slideDown( "fast" );
		} );

		$( ".remove-custom-visibility" ).click( function() {
			$( ".custom-visibility-groups" ).slideUp( "fast" );
			$( "#custom-visibility-radio-custom" ).prop( "checked", false );
			$( ".custom-visibility-groups input[type='checkbox']" ).each( function( x, el ) {
				if ( true === $( el ).prop( "checked" ) ) {
					window.checkedCustomGroups[ $( el ).attr( "name" ) ] = true;
					$( el ).prop( "checked", false );
				}
			} );
		} );

		/**
		 * When cancel is clicked under the available options, reset everything back to its original
		 * value and collapse the display.
		 */
		$postVisibilitySelect.find( ".cancel-post-custom-visibility" ).click( function( e ) {
			$postVisibilitySelect.slideUp( "fast" );

			if ( 0 < Object.keys( window.checkedCustomGroups ).length ) {
				$( ".custom-visibility-groups input[type='checkbox']" ).each( function( x, el ) {
					if ( window.checkedCustomGroups.hasOwnProperty( $( el ).attr( "name" ) ) ) {
						$( el ).prop( "checked", true );
					} else {
						$( el ).prop( "checked", false );
					}
				} );
				$( "#custom-visibility-radio-custom" ).prop( "checked", true );
			} else {
				$( "#custom-visibility-radio-" + $( "#hidden-custom-post-visibility" ).val() ).prop( "checked", true );
			}

			if ( true === $( "#custom-visibility-radio-custom" ).prop( "checked" ) ) {
				$( ".remove-custom-visibility" ).each( function( x, el ) { $( el ).prop( "checked", false ); } );
				$( "#hidden-custom-post-visibility" ).val( "public" );
				$( ".custom-visibility-groups" ).slideDown( "fast" );
			}

			$( "#custom-post_password" ).val( $( "#hidden-custom-post-password" ).val() );
			$( "#custom-sticky" ).prop( "checked", $( "#hidden-custom-post-sticky" ).prop( "checked" ) );
			$( "post-custom-visibility-display" ).html( visibility );
			$( "#custom-visibility .edit-custom-visibility" ).show().focus();

			updateText();
			e.preventDefault();
		} );

		/**
		 * When save is clicked under the available options, assign the current radio button values
		 * to the hidden inputs so that it will save along with the post.
		 */
		$postVisibilitySelect.find( ".save-post-custom-visibility" ).click( function( e ) {
			var sticky;

			$postVisibilitySelect.slideUp( "fast" );
			$( "#custom-visibility .edit-custom-visibility" ).show().focus();

			updateText();

			if ( true !== $( "#custom-visibility-radio-custom" ).prop( "checked" ) ) {
				window.checkedCustomGroups = [];
			}

			// Non-public posts can not be sticky.
			if ( "public" !== $postVisibilitySelect.find( "input:radio:checked" ).val() ) {
				$( "#sticky" ).prop( "checked", false );
			}

			if ( $( "#sticky" ).prop( "checked" ) ) {
				sticky = "Sticky";
			} else {
				sticky = "";
			}

			$( "#post-custom-visibility-display" ).html( localizeText( $postVisibilitySelect.find( "input:radio:checked" ).val() + sticky ) );
			e.preventDefault();
		} );

		$postVisibilitySelect.find( "input:radio" ).change( function() {
			updateVisibility();
		} );
	} );
}( jQuery, window ) );
