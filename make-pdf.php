<?php
// ✅ Carica WordPress (modifica il path se hai una cartella diversa)
require_once('D:/xampp/htdocs/wordpress/wp-load.php');

// ✅ Carica dompdf
require_once(dirname(__FILE__) . '/lib/dompdf/vendor/autoload.php');

use Dompdf\Dompdf;
use Dompdf\Options;

// --- Recupera e sanifica i dati del form ---
$note = sanitize_textarea_field($_POST['pgp_note'] ?? '');
$author_id = intval($_POST['pgp_author_id'] ?? 0);
$post_id = intval($_POST['pgp_post_id'] ?? 0);

$Azienda = get_field('Azienda', $post_id) ?: get_the_title($post_id);
$fototessera = get_field('fototessera', $post_id);

// --- Recupera articoli autore ---
$author_name = '';
$author_posts = [];

if ($author_id) {
    $author = get_user_by('ID', $author_id);
    if ($author) {
        $author_name = $author->display_name;

        $query = new WP_Query([
            'author' => $author_id,
            'post_type' => 'post',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);

        while ($query->have_posts()) {
            $query->the_post();
            $author_posts[] = [
                'title' => get_the_title(),
                'content' => get_the_content()
            ];
        }
        wp_reset_postdata();
    }
}

// --- Costruzione HTML per il PDF ---
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #4A3F35; }
        .cover { background-color: #F5F0E4; text-align: center; padding: 100px; }
        .cover img { max-width: 80%; margin-bottom: 20px; }
        h1 { font-size: 24px; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <div class="cover">
        <h1 style="font-size: 80px;"><?php echo esc_html($Azienda); ?></h1>
        <?php if ($fototessera): ?>
            <img src="<?php echo esc_url($fototessera); ?>" alt="Foto">
        <?php endif; ?>
    </div>

    <div class="page-break">
        <h6 style="font-size: 10px; text-align: center;"><?php echo esc_html($Azienda); ?></h6>
        <h2>Articoli di <?php echo esc_html($author_name); ?></h2>
        <ul>
            <?php foreach ($author_posts as $post): ?>
                <li><?php echo esc_html($post['title']); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <?php foreach ($author_posts as $post): ?>
        <div class="page-break">
            <h6 style="font-size: 10px; text-align: center;"><?php echo esc_html($Azienda); ?></h6>
            <h2><?php echo esc_html($post['title']); ?></h2>
            <div><?php echo apply_filters('the_content', $post['content']); ?></div>
        </div>
    <?php endforeach; ?>

    <div class="page-break">
        <h6 style="font-size: 10px; text-align: center;"><?php echo esc_html($Azienda); ?></h6>
        <h2>Note</h2>
        <p><?php echo nl2br(esc_html($note)); ?></p>
    </div>
</body>
</html>
<?php
$html = ob_get_clean();

// --- Genera PDF ---
$options = new Options();
$options->set('defaultFont', 'DejaVu Sans');
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// --- Aggiungi numero di pagina (escludi la prima pagina / copertina) ---
$canvas = $dompdf->getCanvas();
$canvas->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) use ($Azienda) {
    if ($pageNumber > 1) {
        // Font per footer
        $font = $fontMetrics->getFont("DejaVu Sans", "normal");

        // Calcola numerazione visiva (escludendo copertina)
        $visiblePage = $pageNumber - 1;
        $visibleCount = $pageCount - 1;

        $text = "Pagina $visiblePage di $visibleCount";

        // Calcolo posizione centrata
        $width = $canvas->get_width();
        $height = $canvas->get_height();
        $textWidth = $fontMetrics->getTextWidth($text, $font, 10);

        $canvas->text(
            ($width - $textWidth) / 2, // centrato
            $height - 40,              // margine inferiore
            $text,
            $font,
            10,
            [0.3, 0.3, 0.3] // grigio scuro
        );
    }
});

// --- Output PDF ---
$filename = 'report-' . sanitize_title($Azienda) . '.pdf';
$dompdf->stream($filename, ['Attachment' => 0]);
exit;
