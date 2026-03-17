<?php
use WP_VGWORT\Common;
use WP_VGWORT\Services;

function enqueue_vgw_metis_sidebar_script() {
    wp_enqueue_script(
        'vgw-metis-sidebar-script',
        plugins_url( '../../admin/js/vgw-metis-sidebar.js', __FILE__ ),
        array( 'jquery', 'wp-plugins', 'wp-edit-post', 'wp-i18n', 'wp-element', 'wp-components' ),
        filemtime( plugin_dir_path( __FILE__ ) . '../../admin/js/vgw-metis-sidebar.js' ),
        true
    );


    $auto_add_posts = get_option('wp_metis_pixel_auto_add_posts', '');
    $auto_add_pages = get_option('wp_metis_pixel_auto_add_pages', '');

    $private_identification_id = null;
    $public_identification_id = null;
    $text_type = Common::TEXT_TYPE_LYRIC;
    if (isset($_GET['post'])) {
        $post_id = intval($_GET['post']);

        $pixel       = Services::get_pixel_for_post( $post_id, true );
        $private_identification_id = ($pixel)?
                                        $pixel->get_private_identification_id():
                                        '';
        $public_identification_id = ($pixel)?
                                        $pixel->get_public_identification_id():
                                        '';
        
    }
    
    wp_localize_script('vgw-metis-sidebar-script', 'VGWMetisAjax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('wp_metis_metabox_nonce'),
        'autoAddPosts' => $auto_add_posts,
        'autoAddPages' => $auto_add_pages,
        'privateIdentificationId' => $private_identification_id,
        'publicIdentificationId' => $public_identification_id,
        'text_type' => $text_type,
        'messages' => [
            'enter_pixel_message'          => esc_html__( 'Bitte geben Sie den öffentliche Identifikations-ID der Zählmarke ein', 'vgw-metis' ),
            'confirm_disable_message'      => esc_html__( 'Die bereits mit dem Eintrag verknüpfte Zählmarke wird sofort ungültig und kann nicht mehr verwendet werden. Allfällige Zählungen über diese Zählmarke gehen dabei verloren! Sind Sie sich sicher, dass Sie die neue Zählmarke trotzdem einfügen möchten?', 'vgw-metis' ),
            'ajax_url'                     => admin_url( 'admin-ajax.php' ),
            'yes'                          => esc_html__( 'Ja', 'vgw-metis' ),
            'no'                           => esc_html__( 'Nein', 'vgw-metis' ),
            'error_inserting_pixel'        => esc_html__( 'Fehler! API konnte neue Zählmarke nicht einfügen.', 'vgw-metis' ),
            'error_general'                => esc_html__( 'Ein Fehler ist aufgetreten!', 'vgw-metis' ),
            'error_has_same_post_id'       => esc_html__( 'Fehler! Zählmarke ist hier bereits zugewiesen!', 'vgw-metis' ),
            'error_assign_to_post_failed'  => esc_html__( 'Fehler beim zuweisen der Zählmarke', 'vgw-metis' ),
            'error_remove_pixel_from_post' => esc_html__( 'Fehler beim entfernen der bisherigen Zählmarke!', 'vgw-metis' ),
            'error_disable_pixel'          => esc_html__( 'Fehler beim ungültig setzen der bisherigen Zählmarke!', 'vgw-metis' ),
            'multiple_assignment'          => esc_html__( 'Diese Zählmarke wird bereits für einen anderen Text verwendet oder reserviert. Bitte beachten Sie, dass eine Zählmarke nur für Varianten (z.B. Übersetzungen) oder Teile des gleichen Textes verwendet werden darf.', 'vgw-metis' ),
            'success'                      => esc_html__( 'Manuelle Zuweisung erfolgreich!', 'vgw-metis' ),
            'error_new_pixel_is_disabled'  => esc_html__( 'Fehler: Die neue Zählmarke ist ungültig.', 'vgw-metis' ),
            'status_valid'                 => Common::API_STATE_VALID,
            'status_not_valid'             => Common::API_STATE_NOT_VALID,
            'status_not_found'             => Common::API_STATE_NOT_FOUND,
            'status_not_owner'             => Common::API_STATE_NOT_OWNER,
            'error_is_valid_and_ownership' => esc_html__( 'Fehler: API Aufruf zur Prüfung der Zählmarke ist fehlgeschlagen!', 'vgw-metis' ),
            'status_not_found_message'     => esc_html__( 'Fehler: Zählmarke wurde nicht gefunden', 'vgw-metis' ),
            'status_not_valid_message'     => esc_html__( 'Fehler: Ungültiges Zählmarken-Format', 'vgw-metis' ),
            'not_own_pixel_confirmation'   => esc_html__( 'Es handelt sich nicht um Ihre eigene Zählmarke, möchten Sie diese trotzdem hinzufügen?', 'vgw-metis' ),
            'error_get_posts_count'        => esc_html__( 'Fehler: Anzahl der Beiträge dieser Zählmarke konnte nicht gefunden werden.', 'vgw-metis' ),
            'invalid_format'               => esc_html__( 'Fehler: Ungültiges Zählmarken-Format.', 'vgw-metis' ),
            'removal_failed'               => esc_html__( 'Fehler: Ein vorhandener Pixel konnte nicht erfolgreich entfernt werden.', 'vgw-metis' ),
            'invalid_request'              => esc_html__( 'Fehler: Ungültige Anfrage.', 'vgw-metis' ),
            'open_id_required'             => esc_html__( 'Fehler: Öffentlicher identifikationskode ist erforderlich.', 'vgw-metis' ),
            'already_assigned'			   => esc_html__( 'Pixel ist diesem Beitrag bereits zugewiesen.', 'vgw-metis')
        ]
    ));
}
add_action( 'enqueue_block_editor_assets', 'enqueue_vgw_metis_sidebar_script' );