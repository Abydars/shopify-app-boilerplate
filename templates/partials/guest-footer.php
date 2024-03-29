
<?php if ( not_empty( $g["statics"] ) ): ?>
	<?php if ( not_empty( $g["statics"]["scripts"] ) ): ?>
		<?php foreach ( $g["statics"]["scripts"] as $file_path ): ?>
            <script src="<?php echo( $file_path ); ?>"></script>
		<?php endforeach; ?>
	<?php endif; ?>
<?php endif; ?>

</body>
</html>
