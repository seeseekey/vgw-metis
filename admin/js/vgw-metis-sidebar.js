/**
 * JS for Gutenberg Sidebar
 *
 * @package     vgw-metis
 * @copyright   Verwertungsgesellschaft Wort
 * @license     https://www.gnu.org/licenses/gpl-3.0.html
 * @author      Bojan Kraut
 *
 */

(function ($, wp) {
    const { registerPlugin } = wp.plugins;
    const { PluginDocumentSettingPanel } = wp.editor;
    const { createElement: el, useState, useEffect } = wp.element;
    const { TextControl, RadioControl, Button } = wp.components;
    const { __ } = wp.i18n;
    const { useSelect, useDispatch } = wp.data;

    var autoAssignPixel = 'false';

    function isNewPost() {
        const postStatus = useSelect((select) => select('core/editor').getEditedPostAttribute('status'));
        return postStatus === 'auto-draft';
    }

    function VGWMetisDocumentSettings() {

        var isNew = isNewPost();
        const { editPost } = useDispatch('core/editor');
        const postId = wp.data.useSelect(select => select('core/editor').getCurrentPostId());
        const meta = useSelect(select => select('core/editor').getEditedPostAttribute('meta'), []);

        // Get the current post object
        const post = useSelect( ( select ) => select( 'core/editor' ).getCurrentPost(), [] );

        const [pixelAutoInsertForPost, setPixelAutoInsertForPost] = useState(meta.vgw_metis_counter_auto_insert || 'true');
        const [currentPublicPixelId, setCurrentPublicPixelId] = useState( post.public_identification_id || '' );
        const [assignedPostsCount, setAssignedPostsCount] = useState( 0 );
        const [publicPixelId, setPublicPixelId] = useState( post.public_identification_id || '' );
        const [privatePixelId, setPrivatePixelId] = useState( post.private_identification_id || '' );

        let charCount = meta._metis_text_length;
        
        const { isSavingPost, isAutosavingPost } = useSelect((select) => ({
            isSavingPost: select('core/editor').isSavingPost(),
            isAutosavingPost: select('core/editor').isAutosavingPost(),
        }));

        useEffect(() => {
            if (isSavingPost && !isAutosavingPost) {
                // This effect will run when the post is being saved (but not autosaved)
                if(pixelAutoInsertForPost === 'true') {
                    assignPixelToPost(postId);
                    setPixelAutoInsertForPost('false');
                    post.private_identification_id = privatePixelId;
                    post.public_identification_id = publicPixelId;
                }
            }
        }, [isSavingPost, isAutosavingPost]);

        const [isManualPixelInserted, setManualPixelInserted] = useState(false);
        
        const updateMetaField = (field, value) => {
            editPost({
                meta: {
                    [field]: value
                }
            });
        };
        
        // Using useEffect to manage the setting of pixelAutoInsertForPost
        useEffect(() => {

            // Check if the body has the post-type-post class
            if (jQuery('body').hasClass('post-type-post')) {
                setPixelAutoInsertForPost(VGWMetisAjax.autoAddPosts === 'no' ? 'false' : 'true');
            } 
            // Check if the body has the post-type-page class
            else if (jQuery('body').hasClass('post-type-page')) {
                setPixelAutoInsertForPost(VGWMetisAjax.autoAddPages === 'no' ? 'false' : 'true');
            }

            if(publicPixelId != null) 
                getPostsCount(publicPixelId);

        }, []); // Empty dependency array ensures this only runs once, when the component mounts
        
        // check if we already have a pixel and show disable message
        // yes > confirm to assign new one and disable old one
        // no  > assign new pixel
        function step_has_previous_pixel(current_pid, new_pid, post_id, posts_count, nonce) {
            if (current_pid && current_pid !== '-') {
                if(posts_count < 2) {
                    const sure = confirm(VGWMetisAjax.messages.confirm_disable_message);
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
            // wp ajax call to assign pixel
            $.post(VGWMetisAjax.ajax_url, {
                    action: 'manually_assign_pixel_to_post',
                    post_id: post_id,
                    public_identification_id: new_pid,
                    nonce: nonce
                }, function (data) {
                    // handle response data, show success or error messages
                    if (data) {
                        switch (data) {
                            case 'invalid-format':
                                alert(VGWMetisAjax.messages.invalid_format);
                                break;
                            case 'removal-failed':
                                alert(VGWMetisAjax.messages.removal_failed);
                                break;
                            case 'invalid-request':
                                alert(VGWMetisAjax.messages.invalid_request);
                                break;
                            case 'open-id-required':
                                alert(VGWMetisAjax.messages.open_id_required);
                                break;
                            case 'already-assigned':
                                alert(VGWMetisAjax.messages.already_assigned);
                                break;
                            case 'assign-failed':
                                alert(VGWMetisAjax.messages.assign_failed);
                                break;
                            case 'error-has-same-post-id':
                                alert(VGWMetisAjax.messages.error_has_same_post_id);
                                break;
                            case 'error-assign-to-post-failed':
                                alert(VGWMetisAjax.messages.error_assign_to_post_failed);
                                break;
                            case 'error-remove-pixel-from-post':
                                alert(VGWMetisAjax.messages.error_remove_pixel_from_post);
                                break;
                            case 'error-new-pixel-is-disabled':
                                alert(VGWMetisAjax.messages.error_new_pixel_is_disabled);
                                break;
                            case 'error-inserting-pixel':
                                alert(VGWMetisAjax.messages.error_inserting_pixel);
                                break;
                            case 'multiple-assignment':
                                // alert success message and save / reload page
                                alert(VGWMetisAjax.messages.multiple_assignment);
                                alert(VGWMetisAjax.messages.success);
                                setCurrentPublicPixelId(new_pid);
                                if(new_pid != null) 
                                    getPostsCount(new_pid);
                                document.getElementById('publish').click();
                                break;
                            case 'success':
                                // alert success message and save / reload page
                                alert(VGWMetisAjax.messages.success);
                                setCurrentPublicPixelId(new_pid);
                                if(new_pid != null) 
                                    getPostsCount(new_pid);
                                document.getElementById('publish').click();
                            break;
                        }
                    } else {
                        alert(wp_metis_metabox_obj.error_general);
                        return;
                    }
                }
            );
        }

        const getPostsCount = (publicIdentificationId) => {
            $.ajax({
                url: VGWMetisAjax.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_posts_count',
                    public_identification_id: publicIdentificationId,
                    security: VGWMetisAjax.nonce
                },
                success: function (response) {
                    if (response.success) {
                        setAssignedPostsCount(response.data.posts_count);
                    } else {
                        alert(response.data.message);
                    }
                    if(response.data && response.data.message)
                        alert(response.data.message);
                },
                error: function (xhr, status, error) {
                    console.log('AJAX Error:', error);
                }
            });
        };

        const assignPixelToPost = (postId) => {
            $.ajax({
                url: VGWMetisAjax.ajax_url,
                type: 'POST',
                data: {
                    action: 'assign_pixel_to_post',
                    post_id: postId,
                    security: VGWMetisAjax.nonce
                },
                success: function (response) {
                    if (response.success) {
                        if (response.data.public_identification_id) {
                            if(response.data.private_identification_id)
                                setPrivatePixelId(response.data.private_identification_id);
                            setPublicPixelId(response.data.public_identification_id);
                        } else {
                            console.log('Error:', 'Identification ids are missing');
                        }
                    }
                    if(response.data && response.data.message)
                        alert(response.data.message);
                },
                error: function (xhr, status, error) {
                    console.log('AJAX Error:', error);
                }
            });
        };

        const removePixelFromPost = (postId) => {
            $.ajax({
                url: VGWMetisAjax.ajax_url,
                type: 'POST',
                data: {
                    action: 'remove_pixel_from_post',
                    post_id: postId,
                    security: VGWMetisAjax.nonce
                },
                success: function (response) {
                    if (response.success) {
                        setPrivatePixelId(null);
                        setPublicPixelId(null);
                    } else {
                        console.log('Error:', response.data.message);  
						alert(response.data.message);
                    }
                    alert(response.data.message);
                },
                error: function (xhr, status, error) {
                    console.log('AJAX Error:', error);
                    alert(error);
                }
            });
        };

        const checkValidityAndOwnership = (postId, publicPixelId) => {

            const current_public_identification_id = $('#manual-pixel-assignment-button').data('current-public-identification-id');
            const posts_count = $('#manual-pixel-assignment-button').data('posts-count');

            // wp ajax call to assign pixel
            $.post(VGWMetisAjax.ajax_url, {
                action: 'check_validity_and_ownership',
                post_id: postId,
                public_identification_id: publicPixelId,
                nonce: VGWMetisAjax.nonce
                }, function (data) {
                    // handle response
                    if (data) {
                        switch (data) {
                            // pixel is valid, check if post has a previous pixel
                            case VGWMetisAjax.messages.status_valid:
                                step_has_previous_pixel(current_public_identification_id, publicPixelId, postId, posts_count, VGWMetisAjax.nonce);
                                break;
                            // pixel not valid, show message and return
                            case VGWMetisAjax.messages.status_not_valid:
                                alert(VGWMetisAjax.messages.status_not_valid_message);
                                break;
                            // pixel not found, show message and return
                            case VGWMetisAjax.messages.status_not_found:
                                alert(VGWMetisAjax.messages.status_not_found_message);
                                break;
                            // no pixel ownership, confirm if we really want to add the pixel, if yes, check if post has previous pixel
                            case VGWMetisAjax.messages.status_not_owner:
                                const answer = confirm(VGWMetisAjax.messages.not_own_pixel_confirmation);
                                if (answer) {
                                    step_has_previous_pixel(current_public_identification_id, publicPixelId, postId, posts_count, VGWMetisAjax.nonce);
                                }
                                break;
                            // error, show message and return
                            case 'error-is-valid-and-ownership':
                                alert(VGWMetisAjax.messages.error_is_valid_and_ownership);
                                break;
                            // if none of the above, show a general error
                            default:
                                alert(VGWMetisAjax.messages.error_general);
                                break;
                        }
                        // end this
                        return;
                    } else {
                        alert(VGWMetisAjax.messages.error_general);
                        return;
                    }
                }
            );
        }




        return el(
            PluginDocumentSettingPanel, {
                name: 'vgw-metis-document-settings',
                title: __('VGW Metis Zählmarke', 'text-domain'),
                icon: 'admin-generic'
            },

            isNew && el(RadioControl, {
                label: __('Zählmarke automatisch zuweisen', 'text-domain'),
                selected: pixelAutoInsertForPost,
                options: [
                    { label: __('Ja', 'text-domain'), value: 'true' },
                    { label: __('Nein', 'text-domain'), value: 'false' }
                ],
                onChange: value => {
                    setPixelAutoInsertForPost(value);
                    editPost({ metis_auto_insert: value });
                },
                className: 'vgw-metis-radio-control'
            }),

            // Art des Textes
            el(RadioControl, {
                label: __('Art des Textes', 'text-domain'),
                selected: meta._metis_text_type || 'standard',
                options: [
                    { label: __('Lyrik', 'text-domain'), value: 'lyrik' },
                    { label: __('Anderer Text', 'text-domain'), value: 'standard' }
                ],
                onChange: value => updateMetaField('_metis_text_type', value),
                className: 'vgw-metis-radio-control'
            }),

            // Conditionally render "Öffentlicher Identifikationscode" field
            !isNew && isManualPixelInserted && el(TextControl, {
                label: __('Öffentlicher Identifikationscode', 'text-domain'),
                value: publicPixelId || __('', 'text-domain'),
                onChange: value => {
                    setPublicPixelId(value);
                }
            }),

            !isNew && !isManualPixelInserted && el('div', {
                className: 'vgw_metis_open_id_code-label',
                style: { marginBottom: '10px' }
            },
                el('strong', { className: 'components-base-control__label' }, __('Öffentlicher Identifikationscode', 'text-domain').toUpperCase()),
                el('br'),
                el('span', null, publicPixelId || __('', 'text-domain'))
            ),

            !isNew && el('div', {
                className: 'vgw-metis-private-id-code-label',
                style: { marginBottom: '10px' }
            },
                el('strong', { className: 'components-base-control__label' }, __('Privater Identifikationscode', 'text-domain').toUpperCase()),
                el('br'),
                el('span', null, privatePixelId || __('', 'text-domain'))
            ),

            // Zeichenanzahl
            !isNew && el('div', {
                className: 'vgw_metis_number_of_chars-label',
                style: { marginBottom: '10px' }
            },
                el('strong', { className: 'components-base-control__label' }, __('Zeichenanzahl', 'text-domain').toUpperCase()),
                el('br'),
                el('span', null, charCount || __('', 'text-domain'))
            ),

            // Schaltfläche “Zählmarke zuweisen” bzw. “Zählmarke entfernen”
            !isManualPixelInserted && !isNew && el(Button, {
                isPrimary: true,
                onClick: () => {
                    setManualPixelInserted(false);
                    (publicPixelId == null || publicPixelId == "") ?
                        assignPixelToPost(postId) :
                        removePixelFromPost(postId);
                },
                style: {
                    margin: '5px 0'
                }
            }, (publicPixelId != null && publicPixelId != "") ?
                 __('Zählmarke entfernen', 'text-domain') :
                 __('Zählmarke zuweisen', 'text-domain')),

            // Schaltfläche “Zählmarke manuell zuweisen”
            !isNew && el(Button, {
                isPrimary: true,
                id: 'manual-pixel-assignment-button',
                'data-current-public-identification-id': currentPublicPixelId,
                'data-posts-count': assignedPostsCount,
                onClick: () => {
                    if (isManualPixelInserted) {
                        checkValidityAndOwnership(postId, publicPixelId);
                    }
                    setManualPixelInserted(!isManualPixelInserted);
                },
                style: {
                    margin: '5px 0'
                }
            }, !isManualPixelInserted ? __('Zählmarke manuell zuweisen', 'text-domain') : __('Manuelle Zählmarke speichern', 'text-domain')),

        );

    }

    registerPlugin('vgw-metis-document-settings', {
        render: VGWMetisDocumentSettings,
        icon: 'admin-generic'
    });
})(jQuery, window.wp);