<script setup lang="ts">
import {usePage, router} from '@inertiajs/vue3';
import {onMounted, watch} from 'vue';
import "driver.js/dist/driver.css";

const page = usePage();

onMounted(() => {
    setTimeout(() => {
        runTourLogic();
    }, 500);
});

watch(() => page.component, () => {
    setTimeout(() => {
        runTourLogic();
    }, 500);
});

const runTourLogic = async () => {
    if (typeof window === 'undefined') {
        return;
    }

    if (localStorage.getItem('reflect_tour_completed') === 'true') {
        return;
    }

    const {driver} = await import('driver.js');
    const currentStep = localStorage.getItem('reflect_tour_step') || '1';

    if (currentStep === '1' && page.component === 'Interrupt') {
        const d = driver({
            allowClose: false,
            showProgress: true,
            steps: [
                {
                    popover: {
                        title: 'Welcome to Reflect',
                        description: 'This is an intense, brutal reflection tool. It interrupts your day to ask you hard questions about your behavior.',
                    }
                },
                {
                    popover: {
                        title: 'The Ritual',
                        description: 'When a time window opens, you will be prompted to record an audio or text response. If you miss the window, you miss the reflection.',
                    }
                },
                {
                    element: 'textarea',
                    popover: {
                        title: 'Review Before Commit',
                        description: 'Once you write or speak your reflection, you will review it before committing. You only get one chance to submit.',
                    }
                },
                {
                    element: 'nav a[href="/journal"]',
                    popover: {
                        title: 'Your Journal',
                        description: 'All your committed reflections are securely stored. Let\'s go see your Journal.',
                        onNextClick: () => {
                            localStorage.setItem('reflect_tour_step', '2');
                            d.destroy();
                            router.visit('/journal');
                        }
                    }
                }
            ]
        });
        d.drive();
    } else if (currentStep === '2' && page.component === 'Journal') {
        const d = driver({
            allowClose: false,
            showProgress: true,
            steps: [
                {
                    popover: {
                        title: 'The Timeline',
                        description: 'This is where all of your past reflections are grouped by week. You can always review what you\'ve written here.',
                    }
                },
                {
                    element: 'nav a[href="/settings"]',
                    popover: {
                        title: 'Define Your Identity',
                        description: 'Before you can be challenged effectively, you must define who you are. Let\'s go to Settings.',
                        onNextClick: () => {
                            localStorage.setItem('reflect_tour_step', '3');
                            d.destroy();
                            router.visit('/settings');
                        }
                    }
                }
            ]
        });
        d.drive();
    } else if (currentStep === '3' && page.component === 'Settings') {
        const d = driver({
            allowClose: false,
            showProgress: true,
            steps: [
                {
                    element: 'textarea',
                    popover: {
                        title: 'Identity Statement',
                        description: 'Write a bold, uncompromising statement about who you want to be. The AI will hold you accountable to this standard.',
                    }
                },
                {
                    element: 'nav a[href="/armory"]',
                    popover: {
                        title: 'The Armory',
                        description: 'Once you reflect for a week, you compile your responses into a Weekly Summary. Let\'s see the Armory.',
                        onNextClick: () => {
                            localStorage.setItem('reflect_tour_step', '4');
                            d.destroy();
                            router.visit('/armory');
                        }
                    }
                }
            ]
        });
        d.drive();
    } else if (currentStep === '4' && page.component === 'Armory') {
        const d = driver({
            allowClose: false,
            showProgress: true,
            steps: [
                {
                    popover: {
                        title: 'The Armory',
                        description: 'This is where your past weeks are forged into insights.',
                    }
                },
                {
                    element: 'button',
                    popover: {
                        title: 'Finish The Week',
                        description: 'Every Sunday, you will be able to click this button to generate a brutal analysis of your week based on your entries and your Identity Statement.',
                        onNextClick: () => {
                            localStorage.setItem('reflect_tour_completed', 'true');
                            d.destroy();
                            router.visit('/');
                        }
                    }
                }
            ]
        });
        d.drive();
    }
};
</script>
