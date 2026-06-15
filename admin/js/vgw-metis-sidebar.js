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
    const { createElement: el, useState, useEffect, useRef } = wp.element;
    const { TextControl, RadioControl, Button } = wp.components;
    const { __ } = wp.i18n;
    const { useSelect, useDispatch } = wp.data;

    function VGWMetisDocumentSettings() {
        const {
            postId,
            meta,
            post,
            postStatus,
            isSavingPost,
            isAutosavingPost,
            isEditedPostDirty
        } = useSelect((select) => {
            const editor = select('core/editor');

            return {
                postId: editor.getCurrentPostId(),
                meta: editor.getEditedPostAttribute('meta') || {},
                post: editor.getCurrentPost() || {},
                postStatus: editor.getEditedPostAttribute('status'),
                isSavingPost: editor.isSavingPost(),
                isAutosavingPost: editor.isAutosavingPost(),
                isEditedPostDirty: editor.isEditedPostDirty ? editor.isEditedPostDirty() : false
            };
        }, []);
        const { editPost, savePost } = useDispatch('core/editor');
        const isNew = postStatus === 'auto-draft';
        const initialPublicId = post.public_identification_id || VGWMetisAjax.publicIdentificationId || '';
        const initialPrivateId = post.private_identification_id || VGWMetisAjax.privateIdentificationId || '';

        const [pixelAutoInsertForPost, setPixelAutoInsertForPost] = useState(meta.vgw_metis_counter_auto_insert || 'true');
        const [currentPublicPixelId, setCurrentPublicPixelId] = useState(initialPublicId);
        const [assignedPostsCount, setAssignedPostsCount] = useState(0);
        const [publicPixelId, setPublicPixelId] = useState(initialPublicId);
        const [privatePixelId, setPrivatePixelId] = useState(initialPrivateId);
        const [textLength, setTextLength] = useState(meta._metis_text_length || '');
        const [isManualPixelInserted, setManualPixelInserted] = useState(false);
        const [isBusy, setBusy] = useState(false);
        const pendingAutoAssignAfterSave = useRef(false);

        const codeMessages = {
            'invalid-format': VGWMetisAjax.messages.invalid_format,
            'removal-failed': VGWMetisAjax.messages.removal_failed,
            'invalid-request': VGWMetisAjax.messages.invalid_request,
            'open-id-required': VGWMetisAjax.messages.open_id_required,
            'already-assigned': VGWMetisAjax.messages.already_assigned,
            'assign-failed': VGWMetisAjax.messages.assign_failed,
            'error-has-same-post-id': VGWMetisAjax.messages.error_has_same_post_id,
            'error-assign-to-post-failed': VGWMetisAjax.messages.error_assign_to_post_failed,
            'error-remove-pixel-from-post': VGWMetisAjax.messages.error_remove_pixel_from_post,
            'error-new-pixel-is-disabled': VGWMetisAjax.messages.error_new_pixel_is_disabled,
            'error-disable-pixel': VGWMetisAjax.messages.error_disable_pixel,
            'error-inserting-pixel': VGWMetisAjax.messages.error_inserting_pixel,
            'multiple-assignment': VGWMetisAjax.messages.multiple_assignment,
            'remove-failed': VGWMetisAjax.messages.error_remove_pixel_from_post
        };

        const updateMetaField = (field, value) => {
            editPost({
                meta: {
                    [field]: value
                }
            });
        };

        useEffect(() => {
            if (post.public_identification_id && post.public_identification_id !== publicPixelId) {
                setPublicPixelId(post.public_identification_id);
                setCurrentPublicPixelId(post.public_identification_id);
            }

            if (post.private_identification_id && post.private_identification_id !== privatePixelId) {
                setPrivatePixelId(post.private_identification_id);
            }
        }, [post.public_identification_id, post.private_identification_id]);

        useEffect(() => {
            if (typeof meta._metis_text_length !== 'undefined' && meta._metis_text_length !== textLength) {
                setTextLength(meta._metis_text_length);
            }
        }, [meta._metis_text_length]);

        useEffect(() => {
            if (jQuery('body').hasClass('post-type-post')) {
                setPixelAutoInsertForPost(VGWMetisAjax.autoAddPosts === 'no' ? 'false' : 'true');
            } else if (jQuery('body').hasClass('post-type-page')) {
                setPixelAutoInsertForPost(VGWMetisAjax.autoAddPages === 'no' ? 'false' : 'true');
            }

            if (initialPublicId && postId) {
                getPostsCount(initialPublicId, postId);
            }
        }, []);

        useEffect(() => {
            if (isNew && pixelAutoInsertForPost === 'true' && isSavingPost && !isAutosavingPost) {
                pendingAutoAssignAfterSave.current = true;
            }
        }, [isNew, pixelAutoInsertForPost, isSavingPost, isAutosavingPost]);

        useEffect(() => {
            if (!pendingAutoAssignAfterSave.current || isSavingPost || isAutosavingPost || !postId) {
                return;
            }

            pendingAutoAssignAfterSave.current = false;
            assignPixelToPost(postId, { saveFirst: false, successMessage: VGWMetisAjax.messages.assign_success }).then(function () {
                setPixelAutoInsertForPost('false');
            });
        }, [isSavingPost, isAutosavingPost, postId]);

        function ajaxPost(data) {
            return new Promise(function (resolve, reject) {
                $.ajax({
                    url: VGWMetisAjax.ajax_url,
                    type: 'POST',
                    dataType: 'json',
                    data: data
                }).done(resolve).fail(reject);
            });
        }

        async function saveEditedPostIfDirty() {
            if (!isEditedPostDirty || isSavingPost) {
                return true;
            }

            try {
                await savePost();
                return true;
            } catch (error) {
                alert(VGWMetisAjax.messages.save_error || VGWMetisAjax.messages.error_general);
                return false;
            }
        }

        function applyPixelResponse(data) {
            const publicId = data && data.public_identification_id ? data.public_identification_id : '';
            const privateId = data && data.private_identification_id ? data.private_identification_id : '';

            setPublicPixelId(publicId);
            setCurrentPublicPixelId(publicId || '-');
            setPrivatePixelId(privateId);
            setAssignedPostsCount(data && typeof data.posts_count !== 'undefined' ? parseInt(data.posts_count, 10) || 0 : 0);

            if (data && typeof data.text_length !== 'undefined') {
                setTextLength(data.text_length);
            }
        }

        function showResponseMessage(data, fallbackMessage, isSuccess) {
            if (!data) {
                alert(VGWMetisAjax.messages.error_general);
                return;
            }

            if (data.code === 'multiple-assignment') {
                alert(VGWMetisAjax.messages.multiple_assignment);
                alert(fallbackMessage || VGWMetisAjax.messages.success);
                return;
            }

            const codeMessage = data.code ? codeMessages[data.code] : '';
            const message = codeMessage || fallbackMessage || data.message || (isSuccess ? '' : VGWMetisAjax.messages.error_general);

            if (message) {
                alert(message);
            }
        }

        function showAjaxError(xhr) {
            const data = xhr && xhr.responseJSON && xhr.responseJSON.data ? xhr.responseJSON.data : null;
            showResponseMessage(data, null, false);
        }

        async function postPixelAction(requestData, options) {
            const settings = options || {};

            if (settings.saveFirst) {
                const saved = await saveEditedPostIfDirty();
                if (!saved) {
                    return null;
                }
            }

            setBusy(true);

            try {
                const response = await ajaxPost(requestData);
                const data = response && response.data ? response.data : null;

                if (response && response.success && data) {
                    applyPixelResponse(data);
                    showResponseMessage(data, settings.successMessage, true);
                    return data;
                }

                showResponseMessage(data, null, false);
                return null;
            } catch (xhr) {
                showAjaxError(xhr);
                return null;
            } finally {
                setBusy(false);
            }
        }

        function step_has_previous_pixel(current_pid, new_pid, post_id, posts_count, nonce) {
            if (current_pid && current_pid !== '-') {
                if (posts_count < 2) {
                    const sure = confirm(VGWMetisAjax.messages.confirm_disable_message);
                    if (!sure) {
                        return;
                    }
                }
            }

            step_add_manual_pixel(new_pid, post_id, nonce);
        }

        function step_add_manual_pixel(new_pid, post_id, nonce) {
            postPixelAction({
                action: 'manually_assign_pixel_to_post',
                post_id: post_id,
                public_identification_id: new_pid,
                nonce: nonce
            }, {
                saveFirst: true,
                successMessage: VGWMetisAjax.messages.success
            }).then(function (data) {
                if (data && data.assigned) {
                    setManualPixelInserted(false);
                }
            });
        }

        const getPostsCount = (publicIdentificationId, postId) => {
            $.ajax({
                url: VGWMetisAjax.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'get_posts_count',
                    post_id: postId,
                    public_identification_id: publicIdentificationId,
                    security: VGWMetisAjax.nonce
                },
                success: function (response) {
                    if (response.success) {
                        setAssignedPostsCount(response.data.posts_count);
                    } else if (response.data && response.data.message) {
                        alert(response.data.message);
                    }
                },
                error: function () {
                    alert(VGWMetisAjax.messages.error_get_posts_count);
                }
            });
        };

        const assignPixelToPost = (postId, options) => {
            return postPixelAction({
                action: 'assign_pixel_to_post',
                post_id: postId,
                wp_metis_metabox_text_type: meta._metis_text_type || 'standard',
                security: VGWMetisAjax.nonce
            }, {
                saveFirst: options && typeof options.saveFirst !== 'undefined' ? options.saveFirst : true,
                successMessage: options && options.successMessage ? options.successMessage : VGWMetisAjax.messages.assign_success
            });
        };

        const removePixelFromPost = (postId) => {
            return postPixelAction({
                action: 'remove_pixel_from_post',
                post_id: postId,
                security: VGWMetisAjax.nonce
            }, {
                saveFirst: false,
                successMessage: VGWMetisAjax.messages.remove_success
            });
        };

        const checkValidityAndOwnership = (postId, publicPixelId) => {
            $.post(VGWMetisAjax.ajax_url, {
                action: 'check_validity_and_ownership',
                post_id: postId,
                public_identification_id: publicPixelId,
                nonce: VGWMetisAjax.nonce
            }, function (data) {
                if (data) {
                    switch (data) {
                        case VGWMetisAjax.messages.status_valid:
                            step_has_previous_pixel(currentPublicPixelId, publicPixelId, postId, assignedPostsCount, VGWMetisAjax.nonce);
                            break;
                        case VGWMetisAjax.messages.status_not_valid:
                            alert(VGWMetisAjax.messages.status_not_valid_message);
                            break;
                        case VGWMetisAjax.messages.status_not_found:
                            alert(VGWMetisAjax.messages.status_not_found_message);
                            break;
                        case VGWMetisAjax.messages.status_not_owner:
                            const answer = confirm(VGWMetisAjax.messages.not_own_pixel_confirmation);
                            if (answer) {
                                step_has_previous_pixel(currentPublicPixelId, publicPixelId, postId, assignedPostsCount, VGWMetisAjax.nonce);
                            }
                            break;
                        case 'error-is-valid-and-ownership':
                            alert(VGWMetisAjax.messages.error_is_valid_and_ownership);
                            break;
                        default:
                            alert(VGWMetisAjax.messages.error_general);
                            break;
                    }
                    return;
                }

                alert(VGWMetisAjax.messages.error_general);
            }).fail(function () {
                alert(VGWMetisAjax.messages.error_is_valid_and_ownership);
            });
        };

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

            !isNew && isManualPixelInserted && el(TextControl, {
                label: __('Öffentlicher Identifikationscode', 'text-domain'),
                value: publicPixelId || '',
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
                el('span', null, publicPixelId || '')
            ),

            !isNew && el('div', {
                className: 'vgw-metis-private-id-code-label',
                style: { marginBottom: '10px' }
            },
                el('strong', { className: 'components-base-control__label' }, __('Privater Identifikationscode', 'text-domain').toUpperCase()),
                el('br'),
                el('span', null, privatePixelId || '')
            ),

            !isNew && el('div', {
                className: 'vgw_metis_number_of_chars-label',
                style: { marginBottom: '10px' }
            },
                el('strong', { className: 'components-base-control__label' }, __('Zeichenanzahl', 'text-domain').toUpperCase()),
                el('br'),
                el('span', null, textLength || '')
            ),

            !isManualPixelInserted && !isNew && el(Button, {
                isPrimary: true,
                disabled: isBusy || isSavingPost,
                onClick: () => {
                    setManualPixelInserted(false);
                    (publicPixelId == null || publicPixelId === '') ?
                        assignPixelToPost(postId) :
                        removePixelFromPost(postId);
                },
                style: {
                    margin: '5px 0'
                }
            }, (publicPixelId != null && publicPixelId !== '') ?
                __('Zählmarke entfernen', 'text-domain') :
                __('Zählmarke zuweisen', 'text-domain')),

            !isNew && el(Button, {
                isPrimary: true,
                id: 'manual-pixel-assignment-button',
                disabled: isBusy || isSavingPost,
                'data-current-public-identification-id': currentPublicPixelId,
                'data-posts-count': assignedPostsCount,
                onClick: () => {
                    if (isManualPixelInserted) {
                        checkValidityAndOwnership(postId, publicPixelId);
                        return;
                    }

                    setManualPixelInserted(true);
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
