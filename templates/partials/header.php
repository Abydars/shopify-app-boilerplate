<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= ( $g['page_title'] ?? "" ) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
	<?php if ( not_empty( $g["statics"] ) ): ?>
		<?php if ( not_empty( $g["statics"]["styles"] ) ): ?>
			<?php foreach ( $g["statics"]["styles"] as $file_path ): ?>
                <link rel="stylesheet" href="<?php echo( $file_path ); ?>">
			<?php endforeach; ?>
		<?php endif; ?>
	<?php endif; ?>

    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio,line-clamp"></script>
    <script src="<?= a_asset( 'js/tailwind.config.js' ) ?>"></script>
    <script>
        window.url = "<?= a_link( '' ) ?>";
        window.ajx_url = "<?= a_link( '/ajax' ) ?>";
    </script>
</head>
<body>
<div class="flex min-h-full flex-col justify-center px-6 pt-12 lg:px-8">
    <p>
        <a href="<?= a_link( '/dashboard' ) ?>"
           class="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-semibold leading-6 text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Dashboard</a>
    </p>
</div>
