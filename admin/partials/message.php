<?php

namespace WP_VGWORT;

/**
 * Template for the create message view
 *
 * @package     vgw-metis
 * @copyright   Verwertungsgesellschaft Wort
 * @license     https://www.gnu.org/licenses/gpl-3.0.html
 * @author      Torben Gallob
 * @author      Michael Hillebrand
 *
 */
?>
<?php
    if(!empty($this->warning_message)) {
?>
<div class="notice notice-warning">
    <p><?php esc_html_e( $this->warning_message ); ?></p>
</div>
<?php } ?>

<div class="wrap message">
    <h1><?php esc_html_e( 'Meldung erstellen', 'vgw-metis' ); ?></h1>
	<?php esc_html_e( 'VG WORT METIS', 'vgw-metis' ); ?> <?php esc_html_e( $this->plugin::VERSION ); ?>
    <hr/>

    <h2><?php esc_html_e( 'Meldungsdetails', 'vgw-metis' ); ?></h2>
    <form method="post" id="create-message-form" action="admin-post.php">
        <input type="hidden" name="page" value="metis-message"/>
        <input type="hidden" name="post_id" value="<?php echo $this->post_id; ?>"/>
        <input type="hidden" name="action" value="wp_metis_save_message"/>
        <input type="hidden" name="post_id" value="<?php echo esc_html($this->post_id); ?>" />
		<?php wp_nonce_field( 'wp_metis_save_message', 'message-form-nonce' ); ?>
        <table class="form-table">
            <tbody>
            <tr>
                <th scope="row">
                    <label for="public_identification_id"><?php esc_html_e( 'Öffentlicher Identifikationscode', 'vgw-metis' ); ?></label>
                </th>
                <td>
                    <output name="public_identification_id" id="public_identification_id">
						<?php echo esc_html( $this->pixel->public_identification_id ); ?>
                    </output>
                    <input type="hidden" name="public_identification_id"
                           value="<?php echo esc_html( $this->pixel->public_identification_id ); ?>"/>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="private_identification_id"><?php esc_html_e( 'Privater Identifikationscode', 'vgw-metis' ); ?></label>
                </th>
                <td>
                    <output name="private_identification_id" id="private_identification_id">
						<?php echo esc_html( $this->pixel->private_identification_id ); ?>
                    </output>
                    <input type="hidden" name="private_identification_id"
                           value="<?php echo esc_html( $this->pixel->private_identification_id ); ?>"/>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="permalink"><?php esc_html_e( 'Permalink', 'vgw-metis' ); ?></label>
                </th>
                <td>
                    <output name="permalink" id="permalink"><a
                                href="<?php echo esc_url( get_permalink( $this->post_id ) ); ?>"
                                target="_blank"><?php echo esc_url( get_permalink( $this->post_id ) ); ?></a>
                    </output>
                    <input type="hidden" name="permalink"
                           value="<?php echo esc_url( get_permalink( $this->post_id ) ); ?>"/>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="add_url"><?php esc_html_e( 'Weitere URLs', 'vgw-metis' ); ?></label>
                </th>
                <td>
                    <ul class="urls" id="urls">
                    </ul>

                    <button id="add_url" type="button"
                            class="button button-secondary"><?php esc_html_e( 'URL hinzufügen', 'vgw-metis' ); ?></button>

                </td>
            </tr>
            </tbody>
        </table>

        <h2><?php esc_html_e( 'Textdetails', 'vgw-metis' ); ?></h2>

        <table class="form-table">
            <tbody>
            <tr>
                <th scope="row">
                    <label for="title"><?php esc_html_e( 'Titel', 'vgw-metis' ); ?></label>
                </th>
                <td>
                    <output name="title" id="title"><?php echo esc_html( get_the_title( $this->post_id ) ) ?></output>
                    <input type="hidden" name="title"
                           value="<?php echo esc_html( get_the_title( $this->post_id ) ) ?>"/>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="text_type"><?php esc_html_e( 'Textart', 'vgw-metis' ); ?></label>
                </th>
                <td>
                    <output name="text_type"
                            id="text_type"><?php echo esc_html( $this->get_text_type_label() ); ?></output>
                    <input type="hidden" name="text_type" value="<?php echo esc_html( $this->pixel->text_type ); ?>"/>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="text_length"><?php esc_html_e( 'Textlänge', 'vgw-metis' ); ?></label>
                </th>
                <td>
                    <output name="text_length"
                            id="text_length"><?php echo Services::calculate_post_text_length( $this->post_id ); ?></output>
                    <input type="hidden" name="text_length"
                           value="<?php echo Services::calculate_post_text_length( $this->post_id ); ?>"/>
                </td>
            </tr>
            </tbody>
            <tr>
                <th scope="row">
                    <label for="text"><?php esc_html_e( 'Text', 'vgw-metis' ); ?></label>
                </th>
                <td>
                    <textarea disabled
                              id="text"><?php echo Services::get_striped_post_content( $this->post_id ); ?></textarea>
                    <input type="hidden" name="text"
                           value="<?php echo Services::get_striped_post_content( $this->post_id ); ?>"/>
                </td>
            </tr>
        </table>

        <h2><?php esc_html_e( 'Beteiligte', 'vgw-metis' ); ?></h2>

        <div>
            <div id="transfer-list">
            
                <table id="available-participants">
                    <thead>
                    <tr class="table-title-row">
                        <th colspan="5">
                            Verfügbare Beteiligte laut Beteiligtenverwaltung
                        </th>
                    </tr>
                    <tr class="column-titles-row">
                        <th>Vorname</th>
                        <th>Nachname</th>
                        <th>Karteinummer</th>
                        <th>Funktion</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ( $this->participants as $participant ) {
                        if ( $participant['id'] !== $this->current_user_as_participant->id ) {
                            ?>
                            <tr id="available-participant-<?php echo (int) $participant['id']; ?>">
                                <td><?php echo esc_html( $participant['first_name'] ); ?></td>
                                <td><?php echo esc_html( $participant['last_name'] ); ?></td>
                                <td><?php echo esc_html( $participant['file_number'] ); ?></td>
                                <td><?php echo esc_html( List_Table_Participants::participant_select_options[ $participant['involvement'] ] ); ?></td>
                                <td><span class="add-participant dashicons dashicons-arrow-right-alt2"
                                        data-participant='<?php echo esc_html( json_encode( $participant ) ); ?>'></span>
                                </td>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                    </tbody>
                </table>

                <table id="chosen-participants">
                    <thead>
                    <tr class="table-title-row">
                        <th colspan="5" scope="colgroup">
                            Beteiligte am gemeldeten Text
                        </th>
                    </tr>
                    <tr class="column-titles-row">
                        <th id="delimiter"></th>
                        <th id="vorname">Vorname</th>
                        <th id="nachname">Nachname</th>
                        <th id="karteinummer">Karteinummer</th>
                        <th id="funktion">Funktion</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr id="chosen-participant-<?php echo (int) $this->current_user_as_participant->id; ?>" data-participant='<?php echo esc_html( json_encode( $this->current_user_as_participant ) ); ?>'>
                        <td>&nbsp;</td>
                        <td><?php echo esc_html( $this->current_user_as_participant->first_name ); ?></td>
                        <td><?php echo esc_html( $this->current_user_as_participant->last_name ); ?></td>
                        <td><?php echo esc_html( $this->current_user_as_participant->file_number ); ?></td>
                        <td>
                            <select class="participant-function" id="participant-function-select-<?php echo (int) $this->current_user_as_participant->id; ?>">
                                <option
                                    <?php echo $this->current_user_as_participant->involvement === Common::INVOLVEMENT_AUTHOR ? 'selected' : ''; ?>
                                        value="<?php echo Common::INVOLVEMENT_AUTHOR; ?>"
                                ><?php esc_html_e( 'Autor', 'vgw-metis' ); ?></option>
                                <option
                                    <?php echo $this->current_user_as_participant->involvement === Common::INVOLVEMENT_TRANSLATOR ? 'selected' : ''; ?>
                                        value="<?php echo Common::INVOLVEMENT_TRANSLATOR; ?>"
                                ><?php esc_html_e( 'Übersetzer', 'vgw-metis' ); ?></option>
                            </select>
                            <input
                                    type="hidden"
                                    name="participants[]"
                                    id="hidden-participant-<?php echo (int) $this->current_user_as_participant->id; ?>"
                                    value="<?php echo esc_attr( json_encode( $this->current_user_as_participant ) ); ?>"
                            />
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <!-- Add new participant form -->
            <h3><?php esc_html_e( 'Neuen Beteiligten manuell hinzufügen', 'vgw-metis' ); ?></h3>
            <div id="add-new-participant">
                <input type="text" id="new-participant-first-name" placeholder="<?php esc_attr_e( 'Vorname', 'vgw-metis' ); ?>" />
                <input type="text" id="new-participant-last-name" placeholder="<?php esc_attr_e( 'Nachname', 'vgw-metis' ); ?>" />
                <input type="text" id="new-participant-file-number" placeholder="<?php esc_attr_e( 'Karteinummer', 'vgw-metis' ); ?>" />
                <select id="new-participant-function">
                    <option value="<?php echo Common::INVOLVEMENT_AUTHOR; ?>"><?php esc_html_e( 'Autor', 'vgw-metis' ); ?></option>
                    <option value="<?php echo Common::INVOLVEMENT_TRANSLATOR; ?>"><?php esc_html_e( 'Übersetzer', 'vgw-metis' ); ?></option>
                </select>
                <button type="button" id="add-new-participant-btn" class="button button-secondary"><?php esc_html_e( 'Hinzufügen', 'vgw-metis' ); ?></button>
            </div>


            <?php
                // Get the current post ID
                $post_id = get_the_ID();

                // Perform the server-side check
                if ( Tom_Pixels::should_display_optional_functions( $post_id ) ) :
            ?>
            
            <h3><?php esc_html_e( 'Optionale Zusatzfunktionen', 'vgw-metis' ); ?></h3>
            <div id="participant-exclusion">
                <label>
                    <input type="checkbox" id="exclude-self-checkbox">
                    <?php esc_html_e( 'Ich bin am gemeldeten Text nicht als Autor oder Übersetzer beteiligt', 'vgw-metis' ); ?>
                </label>
            </div>

            <?php
                endif;
            ?>

        </div>







        <h2><?php esc_html_e( 'Erklärung zur Verwendung von KI-Systemen', 'vgw-metis' ); ?></h2>

        <p><?php esc_html_e( $this->ai_disclaimer->text); ?></p>

        <select class="ai-disclaimer-answer" id="ai-disclaimer-answer" name="ai_disclaimer_answer">
            <option value="<?php echo esc_attr( json_encode(null) ); ?>"></option>
            <option value="<?php echo esc_attr( json_encode(true) ); ?>">
                <?php echo esc_attr( $this->ai_disclaimer->yesChoice ); ?>
            </option>
            <option value="<?php echo esc_attr( json_encode(false) ); ?>">
                <?php echo esc_attr( $this->ai_disclaimer->noChoice ); ?>
            </option>
        </select>
        <input
                type="hidden"
                name="previous_rejected_ai_disclaimer"
                id="hidden-previous-rejected-ai-disclaimer"
                value="<?php echo esc_html( json_encode( $this->previous_rejected_ai_disclaimer ) ); ?>"/>

        <hr/>

        <button type="submit"
                class="button button-primary"><?php esc_html_e( 'Meldung absenden', 'vgw-metis' ); ?></button>
        <a class="button button-secondary"
           onclick="history.back()"><?php esc_html_e( 'Abbrechen und zurück', 'vgw-metis' ); ?></a>

    </form>
</div>


<script>
jQuery(document).ready(function($) {

    function checkParticipants() {
        var numRows = $('#chosen-participants tbody tr:visible').length;
        if (numRows == 0) {
            // Disable the submit button
            $('button[type="submit"].button-primary').prop('disabled', true);
        } else {
            // Enable the submit button
            $('button[type="submit"].button-primary').prop('disabled', false);
        }
    }

    function addParticipantToChosen(pdata) {
        var newRow = $('<tr id="chosen-participant-' + pdata.id + '">' +
            '<td><span class="remove-participant dashicons dashicons-no-alt"></span></td>' +
            '<td>' + pdata.first_name + '</td>' +
            '<td>' + pdata.last_name + '</td>' +
            '<td>' + pdata.file_number + '</td>' +
            '<td>' +
            '<select class="participant-function" id="participant-function-select-' + pdata.id + '">' +
            '<option value="<?php echo Common::INVOLVEMENT_AUTHOR; ?>" ' + (pdata.involvement === '<?php echo Common::INVOLVEMENT_AUTHOR; ?>' ? 'selected' : '') + '><?php esc_html_e( 'Autor', 'vgw-metis' ); ?></option>' +
            '<option value="<?php echo Common::INVOLVEMENT_TRANSLATOR; ?>" ' + (pdata.involvement === '<?php echo Common::INVOLVEMENT_TRANSLATOR; ?>' ? 'selected' : '') + '><?php esc_html_e( 'Übersetzer', 'vgw-metis' ); ?></option>' +
            '</select>' +
            '<input type="hidden" name="participants[]" id="hidden-participant-' + pdata.id + '" value=\'' + JSON.stringify(pdata) + '\' />' +
            '</td>' +
            '</tr>');

        $('#chosen-participants tbody').append(newRow);

        // Add event listener for removing participants
        newRow.find('.remove-participant').on('click', function() {
            $(this).closest('tr').remove();
            checkParticipants();
        });

        // Add event listener for updating hidden input when function changes
        newRow.find('.participant-function').on('change', function() {
            var hiddenInput = $('#hidden-participant-' + pdata.id);
            var currentData = JSON.parse(hiddenInput.val());
            currentData.involvement = $(this).val();
            hiddenInput.val(JSON.stringify(currentData));
        });
        
        checkParticipants();
    }

    // New code for adding manual participants
    $('#add-new-participant-btn').on('click', function() {
        var newParticipant = {
            id: 'new-' + Date.now(), // Generate a temporary unique ID
            first_name: $('#new-participant-first-name').val(),
            last_name: $('#new-participant-last-name').val(),
            file_number: $('#new-participant-file-number').val(),
            involvement: $('#new-participant-function').val()
        };

        if (newParticipant.first_name && newParticipant.last_name) {
            addParticipantToChosen(newParticipant);
            // Clear the form
            $('#new-participant-first-name, #new-participant-last-name, #new-participant-file-number').val('');
            $('#new-participant-function').val($('#new-participant-function option:first').val());
        } else {
            alert("Bitte geben Sie mindestens Vor- und Nachname ein.");
        }
    });

    // Add a listener for the form submission
    $('#create-message-form').on('submit', function(e) {
        // Prevent the form from submitting immediately
        e.preventDefault();

        // Show an alert to the user
        if (confirm('<?php esc_html_e( "Sind Sie sicher, dass Sie die Meldung absenden möchten?", "vgw-metis" ); ?>')) {
            // If confirmed, remove the first row and submit the form
            if ($('#exclude-self-checkbox').is(':checked')) {
                $('#chosen-participant-1').remove();
            }
            this.submit();
        }
    });

});
</script>