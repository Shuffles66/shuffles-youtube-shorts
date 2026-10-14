(function () {
	'use strict';

	const byId = (id) => document.getElementById(id);
	const form = byId('ssys-settings-form');
	if (!form) return;

	const keyInput = byId('ssys-api-key');
	const channelInput = byId('ssys-channel');
	const playlistInput = byId('ssys-playlist');
	const colsInput = byId('ssys-cols');
	const rowsInput = byId('ssys-rows');
	const shortcodeOutput = byId('ssys-generated-shortcode');

	byId('ssys-toggle-key').addEventListener('click', function () {
		const showing = keyInput.type === 'text';
		keyInput.type = showing ? 'password' : 'text';
		this.textContent = showing ? 'Show' : 'Hide';
		this.setAttribute('aria-pressed', showing ? 'false' : 'true');
	});

	function buildShortcode() {
		const parts = ['shuffles_youtube_shorts'];
		if (channelInput.value.trim()) parts.push('channel="' + channelInput.value.trim().replace(/"/g, '') + '"');
		if (playlistInput.value.trim()) parts.push('playlist="' + playlistInput.value.trim().replace(/"/g, '') + '"');
		parts.push('cols="' + colsInput.value + '"');
		parts.push('rows="' + rowsInput.value + '"');
		shortcodeOutput.textContent = '[' + parts.join(' ') + ']';
	}

	[channelInput, playlistInput, colsInput, rowsInput].forEach(function (input) {
		input.addEventListener('input', buildShortcode);
	});
	buildShortcode();

	byId('ssys-copy-shortcode').addEventListener('click', async function () {
		try {
			await navigator.clipboard.writeText(shortcodeOutput.textContent);
			this.textContent = ssysAdmin.copied;
			setTimeout(() => { this.textContent = ssysAdmin.copy; }, 1600);
		} catch (error) {
			shortcodeOutput.focus();
		}
	});

	byId('ssys-test-connection').addEventListener('click', async function () {
		const button = this;
		const result = byId('ssys-test-result');
		const data = new URLSearchParams({
			action: 'ssys_test_connection',
			nonce: ssysAdmin.nonce,
			api_key: keyInput.value,
			channel: channelInput.value,
			playlist: playlistInput.value
		});

		button.disabled = true;
		button.textContent = ssysAdmin.testing;
		result.className = '';
		result.textContent = '';

		try {
			const response = await fetch(ssysAdmin.ajaxUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: data.toString(),
				credentials: 'same-origin'
			});
			const payload = await response.json();
			result.textContent = payload.data && payload.data.message ? payload.data.message : 'The connection test did not return a result.';
			result.className = payload.success ? 'is-success' : 'is-error';
		} catch (error) {
			result.textContent = 'The connection test could not be completed.';
			result.className = 'is-error';
		} finally {
			button.disabled = false;
			button.textContent = 'Test connection';
		}
	});
})();

