/**
 * JS for metis settings page actions.
 *
 * @package     vgw-metis
 * @copyright   Verwertungsgesellschaft WORT
 * @license     https://www.gnu.org/licenses/gpl-3.0.html
 */
(function ($) {
	'use strict';

	if (typeof wpMetisSettingsScan === 'undefined') {
		return;
	}

	const config = wpMetisSettingsScan;
	const texts = config.texts || {};
	const $button = $('#btn-scan-page');
	const $progress = $('#wp-metis-scan-progress');
	const $progressBar = $('#wp-metis-scan-progress-bar');
	const $progressText = $('#wp-metis-scan-progress-text');
	const $message = $('#wp-metis-scan-message');

	let totals = {
		processed: 0,
		total: 0,
		new_assigned_pixels: 0,
		already_found: 0,
		failure: 0
	};
	let isRunning = false;

	function formatText(template, replacements) {
		let text = template || '';
		replacements.forEach(function (replacement, index) {
			text = text.replace('%' + (index + 1) + '$s', replacement);
		});
		return text;
	}

	function setRunningState(running) {
		$button.prop('disabled', running).toggleClass('disabled', running);
	}

	function setMessage(message, type) {
		$message
			.removeClass('notice notice-success notice-error')
			.empty();

		if (!message) {
			return;
		}

		$message
			.addClass('notice ' + (type === 'error' ? 'notice-error' : 'notice-success'))
			.append($('<p>', { text: message }));
	}

	function updateProgress() {
		const total = Math.max(0, parseInt(totals.total, 10) || 0);
		const processed = Math.min(total, Math.max(0, parseInt(totals.processed, 10) || 0));
		const percent = total > 0 ? Math.round((processed / total) * 100) : 100;

		$progress.prop('hidden', false);
		$progressBar.val(percent);
		$progressText.text(formatText(texts.progress, [processed, total]));
	}

	function handleError(response) {
		const message = response && response.responseJSON && response.responseJSON.data && response.responseJSON.data.message
			? response.responseJSON.data.message
			: texts.error;

		isRunning = false;
		setRunningState(false);
		setMessage(message, 'error');
	}

	function finishScan() {
		isRunning = false;
		setRunningState(false);
		updateProgress();
		setMessage(
			texts.finished + ' ' + formatText(texts.summary, [
				totals.new_assigned_pixels,
				totals.already_found,
				totals.failure
			]),
			'success'
		);
	}

	function scanBatch(offset) {
		$.post(config.ajaxUrl, {
			action: 'wp_metis_scan_pixels_batch',
			nonce: config.nonce,
			offset: offset,
			batch_size: config.batchSize
		})
			.done(function (response) {
				if (!response || !response.success || !response.data) {
					handleError();
					return;
				}

				const data = response.data;
				totals.processed += parseInt(data.processed, 10) || 0;
				totals.total = parseInt(data.total, 10) || totals.total;
				totals.new_assigned_pixels += parseInt(data.new_assigned_pixels, 10) || 0;
				totals.already_found += parseInt(data.already_found, 10) || 0;
				totals.failure += parseInt(data.failure, 10) || 0;
				updateProgress();

				if (data.done) {
					finishScan();
					return;
				}

				scanBatch(parseInt(data.next_offset, 10) || totals.processed);
			})
			.fail(handleError);
	}

	function startScan(event) {
		event.preventDefault();

		if (isRunning) {
			return;
		}

		isRunning = true;
		totals = {
			processed: 0,
			total: 0,
			new_assigned_pixels: 0,
			already_found: 0,
			failure: 0
		};

		setRunningState(true);
		setMessage('', '');
		$progressBar.val(0);
		$progressText.text(texts.running);
		$progress.prop('hidden', false);

		$.post(config.ajaxUrl, {
			action: 'wp_metis_scan_pixels_init',
			nonce: config.nonce
		})
			.done(function (response) {
				if (!response || !response.success || !response.data) {
					handleError();
					return;
				}

				totals.total = parseInt(response.data.total, 10) || 0;
				updateProgress();

				if (totals.total === 0) {
					finishScan();
					return;
				}

				scanBatch(0);
			})
			.fail(handleError);
	}

	$button.on('click', startScan);
})(jQuery);
