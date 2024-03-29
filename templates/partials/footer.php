
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

<?php if ( not_empty( $g["statics"] ) ): ?>
	<?php if ( not_empty( $g["statics"]["scripts"] ) ): ?>
		<?php foreach ( $g["statics"]["scripts"] as $file_path ): ?>
			<script src="<?php echo( $file_path ); ?>"></script>
		<?php endforeach; ?>
	<?php endif; ?>
<?php endif; ?>

</body>
</html>
