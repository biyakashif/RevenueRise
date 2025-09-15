<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

const page = usePage();
const { translations } = page.props;
const t = (key) => translations[key] || key;

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});
</script>

<template>
    <Head :title="t('JumpTask - Make Money Online')" />
    <div class="min-h-screen bg-white">
        <!-- Header -->
        <header class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <Link :href="route('dashboard')" class="flex items-center">
                        <ApplicationLogo class="h-8 w-auto fill-current text-blue-600" />
                        <span class="ml-2 text-xl font-bold text-gray-800">JumpTask</span>
                    </Link>
                    <nav v-if="canLogin" class="flex items-center space-x-4">
                        <Link
                            v-if="page.props.auth.user"
                            :href="route('dashboard')"
                            class="text-blue-600 hover:text-blue-800 font-medium"
                        >
                            {{ t('Dashboard') }}
                        </Link>
                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="text-blue-600 hover:text-blue-800 font-medium"
                            >
                                {{ t('Log in') }}
                            </Link>
                        </template>
                    </nav>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h1 class="text-4xl md:text-6xl font-bold mb-6">
                    Simple Ways to Make Money Online
                </h1>
                <p class="text-xl md:text-2xl mb-8 max-w-3xl mx-auto">
                    Earn effortlessly from anywhere — no skills, no experience, just your time.
                </p>
                <div class="flex flex-wrap justify-center gap-4 mb-12">
                    <img src="/images/platforms/mafia-city.png" alt="Mafia City" class="h-12 mx-2">
                    <img src="/images/platforms/honeygain.png" alt="Honeygain" class="h-12 mx-2">
                    <img src="/images/platforms/tiktok.png" alt="TikTok" class="h-12 mx-2">
                    <img src="/images/platforms/youtube.png" alt="YouTube" class="h-12 mx-2">
                    <img src="/images/platforms/revolut.png" alt="Revolut" class="h-12 mx-2">
                    <img src="/images/platforms/uber.png" alt="Uber" class="h-12 mx-2">
                    <img src="/images/platforms/aliexpress.png" alt="AliExpress" class="h-12 mx-2">
                </div>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <Link
                        v-if="!page.props.auth.user"
                        :href="route('register')"
                        class="bg-white text-blue-600 px-8 py-3 rounded-full font-semibold text-lg hover:bg-gray-100 transition-all duration-300"
                    >
                        Start Earning Now
                    </Link>
                    <Link
                        v-if="page.props.auth.user"
                        :href="route('dashboard')"
                        class="bg-white text-blue-600 px-8 py-3 rounded-full font-semibold text-lg hover:bg-gray-100 transition-all duration-300"
                    >
                        Go to Dashboard
                    </Link>
                </div>
            </div>
        </section>

        <!-- How It Works -->
        <section class="py-16 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-center mb-12 text-gray-800">
                    How does JumpTask work for online money earning
                </h2>
                <div class="grid md:grid-cols-3 gap-8">
                    <div class="text-center">
                        <div class="bg-blue-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                            <span class="text-2xl font-bold text-blue-600">1</span>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Sign up</h3>
                        <p class="text-gray-600">Create an account and get started.</p>
                    </div>
                    <div class="text-center">
                        <div class="bg-blue-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                            <span class="text-2xl font-bold text-blue-600">2</span>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Complete tasks</h3>
                        <p class="text-gray-600">Browse and complete various tasks to earn rewards.</p>
                    </div>
                    <div class="text-center">
                        <div class="bg-blue-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                            <span class="text-2xl font-bold text-blue-600">3</span>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Get paid</h3>
                        <p class="text-gray-600">Transfer your earnings to your wallet.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Our Vision -->
        <section class="py-16 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <div class="grid md:grid-cols-3 gap-8">
                    <div class="bg-blue-600 text-white p-6 rounded-lg">
                        <h3 class="text-2xl font-bold">3,622,699</h3>
                        <p>Total paid out</p>
                    </div>
                    <div class="bg-purple-600 text-white p-6 rounded-lg">
                        <h3 class="text-2xl font-bold">128,602</h3>
                        <p>Registered users</p>
                    </div>
                    <div class="bg-gray-800 text-white p-6 rounded-lg">
                        <h3 class="text-2xl font-bold">2,636,505</h3>
                        <p>Completed payouts</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Ways to Earn -->
        <section class="py-16 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-center mb-12 text-gray-800">
                    Ways to earn money with JumpTask
                </h2>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div class="bg-gray-50 p-6 rounded-lg shadow-sm text-center">
                        <div class="mb-4">📋</div>
                        <h3 class="text-xl font-semibold mb-2">Take Surveys</h3>
                        <p class="text-gray-600">Participate in surveys and earn rewards.</p>
                    </div>
                    <div class="bg-gray-50 p-6 rounded-lg shadow-sm text-center">
                        <div class="mb-4">🎮</div>
                        <h3 class="text-xl font-semibold mb-2">Play Games</h3>
                        <p class="text-gray-600">Earn by playing games and achieving milestones.</p>
                    </div>
                    <div class="bg-gray-50 p-6 rounded-lg shadow-sm text-center">
                        <div class="mb-4">📱</div>
                        <h3 class="text-xl font-semibold mb-2">Social Media Tasks</h3>
                        <p class="text-gray-600">Engage on social media and get paid.</p>
                    </div>
                    <div class="bg-gray-50 p-6 rounded-lg shadow-sm text-center">
                        <div class="mb-4">📺</div>
                        <h3 class="text-xl font-semibold mb-2">Watch Ads</h3>
                        <p class="text-gray-600">Watch advertisements and earn money.</p>
                    </div>
                    <div class="bg-gray-50 p-6 rounded-lg shadow-sm text-center">
                        <div class="mb-4">🎥</div>
                        <h3 class="text-xl font-semibold mb-2">Watch Videos</h3>
                        <p class="text-gray-600">Earn by watching videos.</p>
                    </div>
                    <div class="bg-gray-50 p-6 rounded-lg shadow-sm text-center">
                        <div class="mb-4">✨</div>
                        <h3 class="text-xl font-semibold mb-2">Other Tasks</h3>
                        <p class="text-gray-600">Complete various micro-tasks.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Why Choose -->
        <section class="py-16 bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-center mb-12 text-gray-800">
                    Why choose JumpTask to earn money online?
                </h2>
                <div class="grid md:grid-cols-2 gap-8">
                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0 bg-blue-100 rounded-full w-12 h-12 flex items-center justify-center">
                            <span class="text-blue-600">📋</span>
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold mb-2">Thousands of tasks for everyone</h3>
                            <p class="text-gray-600">No special skills needed. Complete tasks anytime.</p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0 bg-blue-100 rounded-full w-12 h-12 flex items-center justify-center">
                            <span class="text-blue-600">🌍</span>
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold mb-2">Make money online anytime, anywhere</h3>
                            <p class="text-gray-600">Earn from home or on the go.</p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0 bg-blue-100 rounded-full w-12 h-12 flex items-center justify-center">
                            <span class="text-blue-600">💸</span>
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold mb-2">Get paid online instantly and easily</h3>
                            <p class="text-gray-600">Quick and hassle-free payouts.</p>
                        </div>
                    </div>
                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0 bg-blue-100 rounded-full w-12 h-12 flex items-center justify-center">
                            <span class="text-blue-600">🤝</span>
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold mb-2">24/7 support</h3>
                            <p class="text-gray-600">Our team is here to help anytime.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Devices -->
        <section class="py-16 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-center mb-12 text-gray-800">
                    Earn money online from any device
                </h2>
                <div class="grid md:grid-cols-3 gap-8 text-center">
                    <div>
                        <div class="bg-blue-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                            <span class="text-2xl">📱</span>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Earn on your phone</h3>
                        <p class="text-gray-600">Complete tasks on the go.</p>
                    </div>
                    <div>
                        <div class="bg-blue-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                            <span class="text-2xl">📱</span>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Earn on your tablet</h3>
                        <p class="text-gray-600">Comfortable earning from your tablet.</p>
                    </div>
                    <div>
                        <div class="bg-blue-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                            <span class="text-2xl">💻</span>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Earn on your laptop</h3>
                        <p class="text-gray-600">Higher payouts with focused tasks.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="py-16 bg-blue-600 text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl font-bold mb-6">Start Making Money Now</h2>
                <p class="text-xl mb-8">Turn your spare time into earnings today.</p>
                <Link
                    v-if="!page.props.auth.user"
                    :href="route('register')"
                    class="bg-white text-blue-600 px-8 py-3 rounded-full font-semibold text-lg hover:bg-gray-100 transition-all duration-300"
                >
                    Get Started
                </Link>
            </div>
        </section>
    </div>
</template>