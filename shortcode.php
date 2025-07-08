<?php
function pgp_load_dompdf() {
    $lib_path = plugin_dir_path(__FILE__) . "lib/dompdf/vendor/autoload.php";

    if (file_exists($lib_path)) {
        require_once $lib_path;
    } else {
        die("<h3>ERRORE DI CONFIGURAZIONE NEL PLUGIN PDF-GENERATOR</h3>");
    }
}

pgp_load_dompdf();

add_shortcode('esercitazione-plugin', 'pgp_mostra_form');

function pgp_mostra_form() {
    $id = get_the_ID();
    $Azienda = get_post_meta($id, 'Azienda', true);
    $Fototessera_url = get_field('fototessera', $id);

    $authors = get_users(array(
        'has_published_posts' => array('post'),
        'fields'              => array('ID', 'display_name'),
        'orderby'             => 'display_name',
        'order'               => 'ASC'
    ));

    $action_url = plugins_url('make-pdf.php', __FILE__); // LINK DIRETTO AL FILE PHP

    ob_start();
    ?>
    <form method="post" action="<?php echo esc_url($action_url); ?>">
        <input type="hidden" name="pgp_post_id" value="<?php echo esc_attr($id); ?>">
        <p>
            <label>Azienda:</label><br>
            <?php echo htmlspecialchars($Azienda); ?>
        </p>
        <p>
            <?php if ($Fototessera_url) : ?>
                <img style="width:100px; height:auto; border-radius:5px;" src="<?php echo esc_url($Fototessera_url); ?>" alt="Fototessera">
            <?php endif; ?>
        </p>
        <p>
            <label for="pgp_author_id">Seleziona Autore Articoli:</label><br>
            <select name="pgp_author_id" id="pgp_author_id" required>
                <option value="">Seleziona un autore</option>
                <?php
                foreach ($authors as $author) {
                    echo '<option value="' . esc_attr($author->ID) . '">' . esc_html($author->display_name) . '</option>';
                }
                ?>
            </select>
        </p>
        <p>
            <label for="pgp_note">Note:</label><br>
            <textarea name="pgp_note" id="pgp_note" rows="5" required></textarea>
        </p>
        <p>
            <button type="submit" name="pgp_submit">Genera PDF</button>
        </p>
    </form>
    <?php
    return ob_get_clean();
}
