/**
 * JS for Gutenberg Settings Block
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
        console.log('VGW Metis Gutenberg "save post" JS loaded');

        if (typeof wp !== 'undefined' && typeof wp.data !== 'undefined') {
            function isSavingPost() {

                // State data necessary to establish if a save is occuring.
                const isSaving = wp.data.select('core/editor').isSavingPost() || wp.data.select('core/editor').isAutosavingPost();
                const isSaveable = wp.data.select('core/editor').isEditedPostSaveable();
                const isPostSavingLocked = wp.data.select('core/editor').isPostSavingLocked();
                const hasNonPostEntityChanges = wp.data.select('core/editor').hasNonPostEntityChanges();
                const isAutoSaving = wp.data.select('core/editor').isAutosavingPost();
                const isButtonDisabled = isSaving || !isSaveable || isPostSavingLocked;

                // Reduces state into checking whether the post is saving and that the save button is disabled.
                const isBusy = !isAutoSaving && isSaving;
                const isNotInteractable = isButtonDisabled && !hasNonPostEntityChanges;

                return isBusy && isNotInteractable;
            }

            // Current saving state. isSavingPost is defined above.
            var wasSaving = isSavingPost();

            const { useSelect, useDispatch } = wp.data;

            wp.data.subscribe(() => {

                // New saving state
                let isSaving = isSavingPost();

                // It is done saving if it was saving and it no longer is.
                let isDoneSaving = wasSaving && !isSaving;

                // Update value for next use.
                wasSaving = isSaving;

                if (isDoneSaving) {
                    const post = wp.data.select('core/editor').getCurrentPost();
                    const data = new FormData();
                    data.append('action', 'gutenberg_save_post');
                    data.append('post_id', post.id);
                    data.append('security', wp_metis_gutenberg_obj.nonce);
                    fetch(ajaxurl, {
                        method: 'POST',
                        body: data,
                    })
                    .then(response => response.json())
                    .then(data => {
                        console.log(data);
                        if (data.success) {
                            // Log returned values to the console
                            /*console.log("Text Length:", data.data.text_length);
                            console.log("Public ID:", data.data.public_identification_id);
                            console.log("Private ID:", data.data.private_identification_id);*/
                            
                            // Update post meta using wp.data.dispatch
                            wp.data.dispatch('core/editor').editPost({
                                meta: { _metis_text_length: data.data.text_length }
                            });

                        } else {
                            console.error("Error: Unsuccessful response", data);
                        }
                    })
                    .catch(error => console.error(error));
                } // End of isDoneSaving

            }); // End of wp.data.subscribe
        } else {
            console.warn(wp_metis_gutenberg_obj.gutenberg_not_loaded);
        }
    });
}(jQuery, window, document));
