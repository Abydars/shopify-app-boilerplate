<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= ( $g['page_title'] ?? "" ) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

	<?php if ( not_empty( $g["statics"] ) ): ?>
		<?php if ( not_empty( $g["statics"]["styles"] ) ): ?>
			<?php foreach ( $g["statics"]["styles"] as $file_path ): ?>
                <link rel="stylesheet" href="<?php echo( $file_path ); ?>">
			<?php endforeach; ?>
		<?php endif; ?>
	<?php endif; ?>

    <script src="<?= a_asset('js/jquery-3.5.1.min.js') ?>"></script>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio,line-clamp"></script>
    <script src="<?= a_asset( 'js/tailwind.config.js' ) ?>"></script>
    <script>
        window.url = "<?= a_link( '' ) ?>";
        window.ajx_url = "<?= a_link( '/ajax' ) ?>";
    </script>
</head>
<body>
