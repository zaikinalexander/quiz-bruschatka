const configNode = document.querySelector('[data-quiz-config]');
const config = configNode ? JSON.parse(configNode.textContent) : { general: {}, slides: [], mobileSlides: [] };
const mobileQuery = window.matchMedia('(max-width: 760px)');

const state = {
    currentStep: {
        desktop: 1,
        mobile: 1,
    },
    answers: {
        desktop: {},
        mobile: {},
    },
};

const steps = Array.from(document.querySelectorAll('[data-step][data-flow]'));
const optionButtons = Array.from(document.querySelectorAll('[data-field][data-flow]'));
const progressLabel = document.querySelector('[data-progress-label]');
const progressFill = document.querySelector('[data-progress-fill]');
const quizBackground = document.querySelector('[data-quiz-background]');
const quizCard = document.querySelector('.quiz-card');
const forms = Array.from(document.querySelectorAll('[data-lead-form]'));
const completeScreen = document.querySelector('[data-quiz-complete]');
const goalsCounterId = Number(config?.general?.metrikaCounterId) || 101014580;
const reachedGoals = new Set();

optionButtons.forEach((button) => {
    button.addEventListener('click', () => {
        const { flow, field, value, label, title } = button.dataset;
        const currentStep = state.currentStep[flow];

        state.answers[flow][field] = {
            field,
            value,
            label,
            title,
        };

        if (currentStep === 1) {
            reachYandexGoalOnce('quiz_start', { flow });
            reachYandexGoalOnce(`quiz_${flow}_start`, { flow });
        }

        trackAnswerSelect({
            flow,
            field,
            value,
            label,
            title,
            step: currentStep,
        });

        optionButtons
            .filter((node) => node.dataset.flow === flow && node.dataset.field === field)
            .forEach((node) => node.classList.remove('is-selected'));

        button.classList.add('is-selected');

        window.clearTimeout(button._advanceTimer);
        button._advanceTimer = window.setTimeout(() => {
            const totalSteps = getSteps(flow).length;
            const nextStep = Math.min(state.currentStep[flow] + 1, totalSteps);

            if (nextStep > state.currentStep[flow]) {
                trackStepComplete(flow, state.currentStep[flow], nextStep);
            }

            state.currentStep[flow] = nextStep;
            renderStep();
        }, 180);
    });
});

forms.forEach((form) => {
    const defaultPhoneErrorText = form.querySelector('[data-phone-error]')?.textContent || '';
    const phoneInput = form.elements.phone;
    const submitButton = form.querySelector('[data-submit-button]');
    const phoneError = form.querySelector('[data-phone-error]');
    const successMessage = form.querySelector('[data-success-message]');
    const defaultButtonText = submitButton ? submitButton.textContent : '';

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const flow = form.dataset.flow;
        const answers = getFlowAnswers(flow);
        const phoneTail = normalizePhoneTail(phoneInput.value);
        const phone = phoneTail ? `7${phoneTail}` : '';

        reachYandexGoal('quiz_submit_click', { flow });
        reachYandexGoal(`quiz_${flow}_submit_click`, { flow });
        phoneError.hidden = true;
        phoneError.textContent = defaultPhoneErrorText;
        successMessage.hidden = true;

        if (!isValidPhoneTail(phoneTail)) {
            phoneError.textContent = 'Введите корректный телефон';
            phoneError.hidden = false;
            phoneInput.focus();
            trackSubmitError(flow, 'phone_validation');
            return;
        }

        submitButton.disabled = true;
        submitButton.textContent = 'Отправляем...';
        renderProgress('Готово 100%', 100);

        try {
            const response = await fetch('/api/lead', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    flow,
                    answers,
                    phone,
                    pageUrl: window.location.href,
                }),
            });

            const result = await response.json().catch(() => null);

            if (!response.ok || !result?.success) {
                phoneError.textContent =
                    result?.errors?.phone ||
                    result?.errors?.mail ||
                    result?.errors?.quiz ||
                    'Не удалось отправить заявку';
                phoneError.hidden = false;
                submitButton.disabled = false;
                submitButton.textContent = defaultButtonText;
                trackSubmitError(flow, 'server_response');
                return;
            }
        } catch (error) {
            phoneError.textContent = 'Не удалось отправить заявку';
            phoneError.hidden = false;
            submitButton.disabled = false;
            submitButton.textContent = defaultButtonText;
            trackSubmitError(flow, 'network');
            return;
        }

        if (shouldReachOkZakaz(flow)) {
            reachYandexGoal('ok_zakaz', { flow });
        }
        reachYandexGoal('quiz_submit_success', { flow });
        reachYandexGoal(`quiz_${flow}_submit_success`, { flow });

        window.setTimeout(() => {
            submitButton.disabled = false;
            submitButton.textContent = defaultButtonText;
            form.reset();
            showCompletion(phone, answers);
        }, 250);
    });

    phoneInput.addEventListener('input', (event) => {
        event.target.value = formatPhoneTail(normalizePhoneTail(event.target.value));
    });

    phoneInput.addEventListener('focus', () => {
        const flow = form.dataset.flow;

        reachYandexGoalOnce('quiz_phone_focus', { flow });
        reachYandexGoalOnce(`quiz_${flow}_phone_focus`, { flow });
    });
});

if (typeof mobileQuery.addEventListener === 'function') {
    mobileQuery.addEventListener('change', renderStep);
} else if (typeof mobileQuery.addListener === 'function') {
    mobileQuery.addListener(renderStep);
}

function renderStep() {
    const flow = getCurrentFlow();
    const currentStep = state.currentStep[flow];

    steps.forEach((step) => {
        step.classList.toggle(
            'is-active',
            step.dataset.flow === flow && Number(step.dataset.step) === currentStep,
        );
    });

    const activeStep = getSteps(flow).find((step) => Number(step.dataset.step) === currentStep);

    if (!activeStep) {
        return;
    }

    trackStepView(flow, currentStep, activeStep);

    if (activeStep.dataset.bg) {
        quizBackground.style.backgroundImage =
            `linear-gradient(0deg, rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)), url("${activeStep.dataset.bg}")`;
    }

    renderProgress(activeStep.dataset.progressText, Number(activeStep.dataset.progressWidth));
}

function getCurrentFlow() {
    return mobileQuery.matches ? 'mobile' : 'desktop';
}

function getSteps(flow) {
    return steps.filter((step) => step.dataset.flow === flow);
}

function getFlowAnswers(flow) {
    return Object.values(state.answers[flow] || {});
}

function showCompletion(phone, answers) {
    if (!quizCard || !completeScreen) {
        redirectAfterCompletion(phone, answers);
        return;
    }

    forms.forEach((form) => {
        const successMessage = form.querySelector('[data-success-message]');

        if (successMessage) {
            successMessage.hidden = true;
        }
    });

    completeScreen.hidden = false;
    quizCard.classList.add('is-complete');
    reachYandexGoal('quiz_success_screen_view', {
        flow: getCurrentFlow(),
    });

    if (config.general.submitMode === 'redirect') {
        window.setTimeout(() => {
            redirectAfterCompletion(phone, answers);
        }, getRedirectDelayMs());
    }
}

function redirectAfterCompletion(phone, answers) {
    const target = config.general.redirectUrl || 'https://bruschatka.ru/';
    const url = new URL(target, window.location.origin);
    const flow = getCurrentFlow();

    appendRedirectTrackingParams(url, flow);

    if (config.general.redirectAppendParams === true) {
        url.searchParams.set('phone', phone);

        answers.forEach((answer) => {
            url.searchParams.set(answer.field, answer.value);
        });
    }

    reachYandexGoal('quiz_redirect', {
        target: url.toString(),
    });

    window.setTimeout(() => {
        window.location.href = url.toString();
    }, 150);
}

function appendRedirectTrackingParams(url, flow) {
    url.searchParams.set('utm_source', 'quiz.bruschatka.ru');
    url.searchParams.set('utm_medium', 'quiz');
    url.searchParams.set('utm_campaign', 'bruschatka_quiz');
    url.searchParams.set('utm_content', `${flow}_success_redirect`);
}

function getRedirectDelayMs() {
    const delay = Number(config.general.redirectDelayMs);

    if (!Number.isFinite(delay) || delay < 0) {
        return 7000;
    }

    return delay;
}

function renderProgress(text, width) {
    progressLabel.textContent = text;
    progressFill.style.width = `${width}%`;
}

function trackStepView(flow, step, activeStep) {
    const params = {
        flow,
        step,
        slideId: activeStep.dataset.slideId,
    };

    reachYandexGoalOnce('quiz_page_view');
    reachYandexGoalOnce(`quiz_${flow}_view`, { flow });
    reachYandexGoalOnce('quiz_step_view', params, `quiz_step_view:${flow}:${step}`);
    reachYandexGoalOnce(`quiz_${flow}_step_${step}_view`, params);
}

function trackStepComplete(flow, step, nextStep) {
    const params = {
        flow,
        step,
        nextStep,
    };

    reachYandexGoal(`quiz_step_${step}`, params);
    reachYandexGoal('quiz_step_complete', params);
    reachYandexGoal(`quiz_${flow}_step_${step}_complete`, params);
}

function trackAnswerSelect({ flow, field, value, label, title, step }) {
    const params = {
        flow,
        field,
        value,
        label,
        title,
        step,
    };
    const fieldPart = normalizeGoalPart(field);
    const valuePart = normalizeGoalPart(value);

    reachYandexGoal('quiz_answer_select', params);
    reachYandexGoal(`quiz_answer_${fieldPart}`, params);
    reachYandexGoal(`quiz_answer_${fieldPart}_${valuePart}`, params);
}

function trackSubmitError(flow, reason) {
    const params = {
        flow,
        reason,
    };

    reachYandexGoal('quiz_submit_error', params);
    reachYandexGoal(`quiz_submit_error_${normalizeGoalPart(reason)}`, params);
    reachYandexGoal(`quiz_${flow}_submit_error`, params);
}

function shouldReachOkZakaz(flow) {
    const areaValue = state.answers?.[flow]?.area?.value;

    return ['100-200', '200-400', '400-plus'].includes(areaValue);
}

function normalizeGoalPart(value) {
    return String(value || 'unknown')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '') || 'unknown';
}

function reachYandexGoal(goalName, params = {}) {
    if (typeof window.ym === 'function') {
        window.ym(goalsCounterId, 'reachGoal', goalName, params);
    }
}

function reachYandexGoalOnce(goalName, params = {}, key = goalName) {
    if (reachedGoals.has(key)) {
        return;
    }

    reachedGoals.add(key);
    reachYandexGoal(goalName, params);
}

function isValidPhoneTail(value) {
    return value.length === 10;
}

function normalizePhoneTail(value) {
    let digits = value.replace(/\D/g, '');

    if (digits.startsWith('7') || digits.startsWith('8')) {
        digits = digits.slice(1);
    }

    return digits.slice(0, 10);
}

function formatPhoneTail(value) {
    const parts = [
        value.slice(0, 3),
        value.slice(3, 6),
        value.slice(6, 8),
        value.slice(8, 10),
    ];

    if (!parts[0]) {
        return '';
    }

    let formatted = `+7 (${parts[0]}`;

    if (parts[0].length === 3) {
        formatted += ')';
    }

    if (parts[1]) {
        formatted += ` ${parts[1]}`;
    }

    if (parts[2]) {
        formatted += `-${parts[2]}`;
    }

    if (parts[3]) {
        formatted += `-${parts[3]}`;
    }

    return formatted;
}

renderStep();
