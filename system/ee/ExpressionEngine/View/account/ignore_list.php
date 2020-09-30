<?php $this->extend('_templates/default-nav'); ?>

<?=form_open($table['base_url'])?>
	<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

	<div class="title-bar">
		<h2 class="title-bar__title">
			<?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?>
		</h2>
		<?php if (isset($filters)) echo $filters; ?>
		<div class="title-bar__extra-tools">
			<div class="search-input">
				<input class="search-input__input" placeholder="<?=lang('search')?>" type="text" name="search" value="<?=htmlentities($table['search'], ENT_QUOTES, 'UTF-8')?>">
			</div>
		</div>
	</div>

	<?php $this->embed('_shared/table', $table); ?>

	<?php if ( ! empty($pagination)) $this->embed('_shared/pagination', $pagination); ?>

	<?php if ( ! empty($table['data'])): ?>
	<?php $this->embed('ee:_shared/form/bulk-action-bar', [
		'options' => [
			[
				'value' => "",
				'text' => '-- ' . lang('with_selected') . ' --'
			],
			[
				'value' => "remove",
				'text' => lang('delete'),
				'attrs' => ' data-confirm-trigger="selected" rel="modal-confirm-remove"'
			]
		],
		'modal' => true
	]); ?>
	<?php endif; ?>
<?=form_close()?>

<?php

$modal_vars = array(
	'name'		=> 'modal-confirm-remove',
	'form_url'	=> $form_url,
	'hidden'	=> array(
		'bulk_action'	=> 'remove'
	)
);

$modal = $this->make('ee:_shared/modal_confirm_remove')->render($modal_vars);
ee('CP/Modal')->addModal('remove', $modal);
?>