/**
 * JS for classic editor metaboxes
 *
 * @package     vgw-metis
 * @copyright   Verwertungsgesellschaft Wort
 * @license     https://www.gnu.org/licenses/gpl-3.0.html
 * @author      Torben Gallob
 * @author      Michael Hillebrand
 *
 */
(function ($, window, document) {
    'use strict';
    // execute when the DOM is ready
    $(document).ready(function () {
        const $manualButton = $('#wp_metis_metabox_pixel_action_manual_assign');
        const $assignButton = $('#wp_metis_metabox_pixel_action_assign');
        const $removeButton = $('#wp_metis_metabox_pixel_action_remove');

        if (!$manualButton.length) {
            return;
        }

        let current_public_identification_id = $manualButton.data('current-public-identification-id') || '';
        const post_id = $manualButton.data('post-id');
        let posts_count = parseInt($manualButton.data('posts-count'), 10) || 0;
        const nonce = $manualButton.data('nonce');

        const codeMessages = {
            'invalid-format': wp_metis_metabox_obj.invalid_format,
            'removal-failed': wp_metis_metabox_obj.removal_failed,
            'invalid-request': wp_metis_metabox_obj.invalid_request,
            'open-id-required': wp_metis_metabox_obj.open_id_required,
            'already-assigned': wp_metis_metabox_obj.already_assigned,
            'assign-failed': wp_metis_metabox_obj.assign_failed,
            'error-has-same-post-id': wp_metis_metabox_obj.error_has_same_post_id,
            'error-assign-to-post-failed': wp_metis_metabox_obj.error_assign_to_post_failed,
            'error-remove-pixel-from-post': wp_metis_metabox_obj.error_remove_pixel_from_post,
            'error-new-pixel-is-disabled': wp_metis_metabox_obj.error_new_pixel_is_disabled,
            'error-disable-pixel': wp_metis_metabox_obj.error_disable_pixel,
            'error-inserting-pixel': wp_metis_metabox_obj.error_inserting_pixel,
            'multiple-assignment': wp_metis_metabox_obj.multiple_assignment,
            'remove-failed': wp_metis_metabox_obj.error_remove_pixel_from_post
        };

        function setBusy(isBusy) {
            $manualButton.add($assignButton).add($removeButton).prop('disabled', isBusy);
        }

        function getSelectedTextType() {
            return $('input[name="wp_metis_metabox_text_type"]:checked').val() || '';
        }

        function setPixelState(data) {
            const publicId = data && data.public_identification_id ? data.public_identification_id : '';
            const privateId = data && data.private_identification_id ? data.private_identification_id : '';
            const textLength = data && typeof data.text_length !== 'undefined' ? data.text_length : '';

            $('#wp_metis_metabox_public_id_value').text(publicId || '-');
            $('#wp_metis_metabox_private_id_value').text(privateId || '-');
            $('#wp_metis_metabox_text_length_value').text(textLength);

            $('.wp_metis_metabox_public_id').toggle(!!publicId);
            $('.wp_metis_metabox_private_id').toggle(!!publicId);
            $('.wp_metis_metabox_char_count').toggle(!!publicId);
            $assignButton.closest('.wp_metis_metabox_action_assign').toggle(!publicId);
            $removeButton.closest('.wp_metis_metabox_action_remove').toggle(!!publicId);

            current_public_identification_id = publicId || '-';
            posts_count = data && typeof data.posts_count !== 'undefined' ? parseInt(data.posts_count, 10) || 0 : 0;
            $manualButton
                .data('current-public-identification-id', current_public_identification_id)
                .data('posts-count', posts_count);
        }

        function showResponseMessage(data, fallbackMessage, isSuccess) {
            if (!data) {
                alert(wp_metis_metabox_obj.error_general);
                return;
            }

            if (data.code === 'multiple-assignment') {
                alert(wp_metis_metabox_obj.multiple_assignment);
                return;
            }

            if (isSuccess) {
                return;
            }

            const codeMessage = data.code ? codeMessages[data.code] : '';
            const message = codeMessage || fallbackMessage || data.message || wp_metis_metabox_obj.error_general;

            if (message) {
                alert(message);
            }
        }

        function handleAjaxError(xhr) {
            const data = xhr && xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data : null;
            showResponseMessage(data, null, false);
        }

        function postPixelAction(requestData, fallbackMessage) {
            setBusy(true);

            return $.ajax({
                url: wp_metis_metabox_obj.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: requestData
            }).done(function (response) {
                const data = response && response.data ? response.data : null;

                if (response && response.success && data) {
                    setPixelState(data);
                    showResponseMessage(data, fallbackMessage, true);
                    return;
                }

                showResponseMessage(data, null, false);
            }).fail(handleAjaxError).always(function () {
                setBusy(false);
            });
        }

        // check if we already have a pixel and show disable message
        // yes > confirm to assign new one and disable old one
        // no  > assign new pixel
        function step_has_previous_pixel(current_pid, new_pid, post_id, posts_count, nonce) {

            if (current_pid && current_pid !== '-') {

                if(posts_count < 2) {
                    const sure = confirm(wp_metis_metabox_obj.confirm_disable_message);
                    // exit if answer is no
                    if (!sure) {
                        return;
                    }

                }
            }
            // finally add the new pixel
            step_add_manual_pixel(new_pid, post_id, nonce);
        }

        // add the manual pixel or display various error messages
        function step_add_manual_pixel(new_pid, post_id, nonce) {
            postPixelAction({
                action: 'wp_metis_metabox_manual_assign_pixel',
                post_id: post_id,
                public_identification_id: new_pid,
                nonce: nonce
            }, wp_metis_metabox_obj.success);
        }

        $assignButton.on('click', function () {
            postPixelAction({
                action: 'assign_pixel_to_post',
                post_id: post_id,
                wp_metis_metabox_text_type: getSelectedTextType(),
                security: nonce
            }, wp_metis_metabox_obj.assign_success);
        });

        $removeButton.on('click', function () {
            postPixelAction({
                action: 'remove_pixel_from_post',
                post_id: post_id,
                security: nonce
            }, wp_metis_metabox_obj.remove_success);
        });

        $manualButton.on('click', () => {
            // ask the user to enter pid of the new pixel
            const new_pid = prompt(wp_metis_metabox_obj.enter_pixel_message);

            if(new_pid !== null) {

                // check ownership / validity
                $.post(wp_metis_metabox_obj.ajax_url, {
                    action: 'wp_metis_metabox_check_validity_and_ownership',
                    post_id: post_id,
                    public_identification_id: new_pid,
                    nonce: nonce
                }, function (data) {
                    // handle response
                    if (data) {
                        switch (data) {
                            // pixel is valid, check if post has a previous pixel
                            case wp_metis_metabox_obj.status_valid:
                                step_has_previous_pixel(current_public_identification_id, new_pid, post_id, posts_count, nonce);
                                break;
                            // pixel not valid, show message and return
                            case wp_metis_metabox_obj.status_not_valid:
                                alert(wp_metis_metabox_obj.status_not_valid_message);
                                break;
                            // pixel not found, show message and return
                            case wp_metis_metabox_obj.status_not_found:
                                alert(wp_metis_metabox_obj.status_not_found_message);
                                break;
                            // no pixel ownership, confirm if we really want to add the pixel, if yes, check if post has previous pixel
                            case wp_metis_metabox_obj.status_not_owner:
                                const answer = confirm(wp_metis_metabox_obj.not_own_pixel_confirmation);
                                if (answer) {
                                    step_has_previous_pixel(current_public_identification_id, new_pid, post_id, posts_count, nonce);
                                }
                                break;
                            // error, show message and return
                            case 'error-is-valid-and-ownership':
                                alert(wp_metis_metabox_obj.error_is_valid_and_ownership);
                                break;
                            // if none of the above, show a general error
                            default:
                                alert(wp_metis_metabox_obj.error_general);
                                break;
                        }
                        // end this
                        return;
                    } else {
                        alert(wp_metis_metabox_obj.error_general);
                        return;
                    }
                });
            }
        });
    });
}(jQuery, window, document));
