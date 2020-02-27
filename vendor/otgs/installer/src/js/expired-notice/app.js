import partial from 'ramda/src/partial';

document.addEventListener('DOMContentLoaded', function () {
	const mailFormat = /^\w+([\.+-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/;

	const find = (element, selector) => element && element.querySelector(selector);

	const main = document.querySelector('.otgs-installer-expired'),
		findSection = partial(find, [main]),
		questionSection = findSection('.js-question'),
		yesSection = findSection('.js-yes-section'),
		noSection = findSection('.js-no-section'),
		accountSection = findSection('.js-find-account-section'),
		checkAccountButton = find(accountSection, '.js-find-account');

	const show = elem => elem.style.display = '';
	const hide = elem => elem.style.display = 'none';

	const showSection = (section, event) => {
		event && event.preventDefault();
		hide(questionSection);
		show(section);
	};

	const onAccountResponse = response => {
		hide(accountSection);
		const section = response.data.found ? yesSection : noSection;
		const p = find(section, 'p');
		p.textContent = p.dataset.alternative;
		showSection(section)
	};

	const onAccountError = () => {
		onAccountResponse({data: {found: false}});
	};

	const createSpinnerAfter = element => {
		const spinner = document.createElement('span');
		spinner.className = 'spinner';
		spinner.style.display = 'inline-block';
		spinner.style.visibility = 'visible';
		spinner.style.float = 'none';
		element.parentNode.insertBefore(spinner, element.nextSibling);
	};

	const checkAccount = event => {
		event.preventDefault();
		const input = find(accountSection, 'input');

		input.disabled = true;
		checkAccountButton.onclick = event => event.preventDefault();
		createSpinnerAfter(checkAccountButton);
		const data = {
			email: input.value,
			repository_id: checkAccountButton.dataset.repository,
			nonce: checkAccountButton.dataset.nonce,
			action: 'find_account'
		};
		otgs_wp_installer.check_account(data, onAccountResponse, onAccountError);
	};

	const isValidEmail = email => email.match(mailFormat);

	const setCheckAccountState = event => {
		if( isValidEmail(event.target.value) ) {
			checkAccountButton.setAttribute( 'href', '#');
			checkAccountButton.onclick = checkAccount;
			checkAccountButton.classList.remove('btn-disabled');
		} else {
			checkAccountButton.setAttribute( 'href', '');
			checkAccountButton.onclick = event => event.preventDefault();
			checkAccountButton.classList.add('btn-disabled');
		}
	};

	if (questionSection) {
		const findQuestionButton = partial(find, [questionSection]);

		findQuestionButton('.js-yes-button').onclick = partial(showSection, [yesSection]);
		findQuestionButton('.js-no-button').onclick = partial(showSection, [noSection]);
		findQuestionButton('.js-dont-know').onclick = partial(showSection, [accountSection]);

		find(accountSection, 'input').oninput = setCheckAccountState;
		checkAccountButton.onclick = event => event.preventDefault();
	}
});
