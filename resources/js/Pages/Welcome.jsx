import { Head, Link } from '@inertiajs/react';
import ApplicationLogo from '@/Components/ApplicationLogo';
import { useState } from 'react';

export default function Welcome({ auth, laravelVersion, phpVersion }) {
    const [activeFeature, setActiveFeature] = useState(0);

    const features = [
        {
            name: 'IRBM Integration',
            description: 'Stay 100% compliant with Malaysia\'s LHDN e-Invoicing mandate. We handle the technical complexity so you don\'t have to. Enjoy seamless submission and real-time tracking.',
            icon: (
                <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />
                </svg>
            )
        },
        {
            name: 'AI Integration',
            description: 'Leverage artificial intelligence to automate data entry, validate invoices, and detect errors before they become problems. Smart suggestions save you hours of manual work.',
            icon: (
                <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
                </svg>
            )
        },
        {
            name: 'Invoice Management',
            description: 'Manage all your invoices in one secure platform. Sort, filter, and track status with ease. Get paid faster with automated reminders and professional templates.',
            icon: (
                <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
            )
        }
    ];

    return (
        <>
            <Head title="Welcome" />
            <div className="min-h-screen bg-slate-50 text-slate-900 dark:bg-zinc-950 dark:text-slate-100 font-sans selection:bg-blue-600 selection:text-white">

                {/* Header */}
                <header className="fixed w-full top-0 z-50 bg-white/80 dark:bg-zinc-950/80 backdrop-blur-md border-b border-slate-200 dark:border-white/5 transition-all duration-300">
                    <div className="max-w-7xl mx-auto px-6 lg:px-8">
                        <div className="flex items-center justify-between h-20">
                            {/* Logo */}
                            <div className="flex-shrink-0 group hover:scale-105 transition-transform duration-300">
                                <ApplicationLogo className="h-10 w-auto" variant="word" />
                            </div>

                            {/* Navigation */}
                            <nav className="flex gap-4 items-center">
                                {auth.user ? (
                                    <Link
                                        href={route('dashboard')}
                                        className="rounded-full bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 dark:focus:ring-offset-zinc-950 shadow-lg shadow-blue-600/20 hover:shadow-blue-600/40 hover:-translate-y-0.5 duration-300"
                                    >
                                        Dashboard
                                    </Link>
                                ) : (
                                    <>
                                        <Link
                                            href={route('login')}
                                            className="hidden sm:block rounded-full px-6 py-2.5 text-sm font-medium text-slate-600 transition hover:text-blue-600 focus:outline-none dark:text-slate-400 dark:hover:text-blue-400"
                                        >
                                            Log in
                                        </Link>
                                        <Link
                                            href={route('register')}
                                            className="rounded-full bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2 dark:focus:ring-offset-zinc-950 shadow-lg shadow-blue-600/20 hover:shadow-blue-600/40 hover:-translate-y-0.5 duration-300"
                                        >
                                            Get Started
                                        </Link>
                                    </>
                                )}
                            </nav>
                        </div>
                    </div>
                </header>

                <main className="pt-20">
                    {/* Hero Section */}
                    <div className="relative isolate pt-14 lg:pt-20 overflow-hidden">
                        {/* Background blobs for premium feel */}
                        <div className="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80" aria-hidden="true">
                            <div className="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-[#3b82f6] to-[#8b5cf6] opacity-20 sm:left-[calc(50%-30rem)] sm:w-[72.1875rem] animate-pulse" style={{ clipPath: 'polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)' }}></div>
                        </div>

                        <div className="py-16 sm:py-24 lg:pb-32">
                            <div className="mx-auto max-w-7xl px-6 lg:px-8">
                                <div className="mx-auto max-w-3xl text-center">
                                    <h1 className="text-4xl font-extrabold tracking-tight text-slate-900 sm:text-6xl dark:text-white mb-6">
                                        Streamline Your <span className="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-violet-500">Invoicing Process</span>
                                    </h1>
                                    <p className="mt-6 text-lg sm:text-xl leading-8 text-slate-600 dark:text-slate-300 max-w-2xl mx-auto">
                                        Create fully compliant e-Invoices powered by AI. Invoisync seamlessly integrates with IRBM to ensure your business stays ahead of regulatory changes.
                                    </p>
                                    <div className="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4 sm:gap-x-6">
                                        <Link
                                            href={route('register')}
                                            className="w-full sm:w-auto rounded-full bg-blue-600 px-8 py-3.5 text-sm font-semibold text-white shadow-xl shadow-blue-600/20 transition-all hover:bg-blue-500 hover:-translate-y-1 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600"
                                        >
                                            Start for free
                                        </Link>
                                        <Link
                                            href={route('login')}
                                            className="w-full sm:w-auto rounded-full px-8 py-3.5 text-sm font-semibold leading-6 text-slate-900 transition-all hover:text-blue-600 hover:bg-slate-100 dark:text-white dark:hover:text-blue-400 dark:hover:bg-white/5"
                                        >
                                            Log in <span aria-hidden="true" className="ml-1 transition-transform group-hover:translate-x-1 inline-block">→</span>
                                        </Link>
                                    </div>
                                </div>
                                {/* Dashboard Screenshot */}
                                <div className="mt-16 sm:mt-24 relative group">
                                    <div className="absolute -inset-1 rounded-2xl bg-gradient-to-r from-blue-600 to-violet-500 opacity-20 blur-xl transition duration-1000 group-hover:opacity-40 group-hover:duration-200"></div>
                                    <div className="relative rounded-2xl bg-slate-900/5 p-2 ring-1 ring-inset ring-slate-900/10 lg:p-4 dark:bg-white/5 dark:ring-white/10 backdrop-blur-sm">
                                        <div className="rounded-xl bg-white dark:bg-zinc-900 shadow-2xl ring-1 ring-slate-900/10 dark:ring-white/10 overflow-hidden relative">
                                            {/* Mockup Window Header */}
                                            <div className="absolute top-0 w-full h-12 bg-slate-100 dark:bg-zinc-800 border-b border-slate-200 dark:border-white/5 flex items-center px-4 gap-2 z-10">
                                                <div className="w-3 h-3 rounded-full bg-rose-500"></div>
                                                <div className="w-3 h-3 rounded-full bg-amber-500"></div>
                                                <div className="w-3 h-3 rounded-full bg-emerald-500"></div>
                                                <div className="mx-auto w-1/3 h-5 bg-white dark:bg-zinc-900 rounded-md shadow-sm border border-slate-200 dark:border-white/5 flex items-center justify-center">
                                                    <div className="w-1/2 h-2 rounded-full bg-slate-200 dark:bg-zinc-800"></div>
                                                </div>
                                            </div>
                                            <div className="pt-12">
                                                <img 
                                                    src="/images/dashboard-preview.png" 
                                                    alt="Invoisync Dashboard" 
                                                    className="w-full h-auto object-cover transform transition-transform duration-700 ease-out hover:scale-[1.02]"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="absolute inset-x-0 top-[calc(100%-13rem)] -z-10 transform-gpu overflow-hidden blur-3xl sm:top-[calc(100%-30rem)]" aria-hidden="true">
                            <div className="relative left-[calc(50%+3rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 bg-gradient-to-tr from-[#3b82f6] to-[#8b5cf6] opacity-20 sm:left-[calc(50%+36rem)] sm:w-[72.1875rem]" style={{ clipPath: 'polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)' }}></div>
                        </div>
                    </div>

                    {/* Interactive Features Section */}
                    <div className="bg-white dark:bg-zinc-900/50 py-24 sm:py-32 border-y border-slate-200 dark:border-white/5 relative overflow-hidden">
                        <div className="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-5"></div>
                        <div className="mx-auto max-w-7xl px-6 lg:px-8 relative z-10">
                            <div className="mx-auto max-w-2xl lg:text-center mb-16">
                                <h2 className="text-base font-semibold leading-7 text-blue-600 tracking-wide uppercase">Future-ready</h2>
                                <p className="mt-2 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl dark:text-white">
                                    Intelligent, Compliant Invoicing
                                </p>
                                <p className="mt-6 text-lg leading-8 text-slate-600 dark:text-slate-400">
                                    Everything you need to manage your billing, automated and secured.
                                </p>
                            </div>

                            <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
                                {/* Feature Selector (Interactive) */}
                                <div className="lg:col-span-5 flex flex-col gap-4">
                                    {features.map((feature, idx) => (
                                        <button
                                            key={idx}
                                            onClick={() => setActiveFeature(idx)}
                                            className={`text-left flex items-start gap-4 p-6 rounded-2xl transition-all duration-300 ${
                                                activeFeature === idx 
                                                ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800 shadow-md transform scale-[1.02]' 
                                                : 'bg-slate-50 dark:bg-zinc-900 hover:bg-slate-100 dark:hover:bg-zinc-800 border-transparent hover:border-slate-200 dark:hover:border-zinc-700'
                                            } border`}
                                        >
                                            <div className={`flex-shrink-0 h-12 w-12 flex items-center justify-center rounded-xl transition-colors duration-300 ${
                                                activeFeature === idx ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/30' : 'bg-blue-100 text-blue-600 dark:bg-zinc-800 dark:text-blue-400'
                                            }`}>
                                                {feature.icon}
                                            </div>
                                            <div>
                                                <h3 className={`text-lg font-semibold mb-2 transition-colors ${
                                                    activeFeature === idx ? 'text-blue-900 dark:text-blue-100' : 'text-slate-900 dark:text-white'
                                                }`}>
                                                    {feature.name}
                                                </h3>
                                                <p className={`text-sm leading-relaxed transition-colors ${
                                                    activeFeature === idx ? 'text-blue-700 dark:text-blue-200/80' : 'text-slate-600 dark:text-slate-400'
                                                }`}>
                                                    {feature.description}
                                                </p>
                                            </div>
                                        </button>
                                    ))}
                                </div>

                                {/* Feature Display */}
                                <div className="lg:col-span-7 relative">
                                    <div className="aspect-[4/3] rounded-2xl bg-gradient-to-br from-slate-100 to-slate-200 dark:from-zinc-800 dark:to-zinc-900 border border-slate-200 dark:border-white/10 p-8 flex items-center justify-center overflow-hidden relative shadow-2xl">
                                        <div className="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/microbial-mat.png')] opacity-10"></div>
                                        
                                        {/* Dynamic content based on selection */}
                                        <div className="relative z-10 w-full max-w-md transition-all duration-500 ease-in-out">
                                            {activeFeature === 0 && (
                                                <div className="bg-white dark:bg-zinc-950 p-6 rounded-xl shadow-xl border border-emerald-100 dark:border-emerald-900/30">
                                                    <div className="flex items-center justify-between mb-4">
                                                        <div className="flex items-center gap-3">
                                                            <div className="w-10 h-10 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400">
                                                                <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2"><path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" /></svg>
                                                            </div>
                                                            <div>
                                                                <p className="text-sm font-medium text-slate-900 dark:text-white">LHDN Connection</p>
                                                                <p className="text-xs text-emerald-600 dark:text-emerald-400">Status: Active</p>
                                                            </div>
                                                        </div>
                                                        <span className="px-2.5 py-1 text-xs font-medium bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400 rounded-full flex items-center gap-1">
                                                            <span className="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                            Validated
                                                        </span>
                                                    </div>
                                                    <div className="space-y-3">
                                                        <div className="h-2 bg-slate-100 dark:bg-zinc-800 rounded-full overflow-hidden">
                                                            <div className="w-full h-full bg-emerald-500"></div>
                                                        </div>
                                                        <div className="flex justify-between text-xs text-slate-500 dark:text-slate-400">
                                                            <span>Syncing latest data...</span>
                                                            <span>100%</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            )}

                                            {activeFeature === 1 && (
                                                <div className="bg-white dark:bg-zinc-950 p-6 rounded-xl shadow-xl border border-violet-100 dark:border-violet-900/30">
                                                    <div className="flex items-start gap-4 mb-4">
                                                        <div className="w-10 h-10 rounded-full bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center text-violet-600 dark:text-violet-400 shrink-0">
                                                            <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2"><path strokeLinecap="round" strokeLinejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                                                        </div>
                                                        <div>
                                                            <p className="text-sm font-medium text-slate-900 dark:text-white mb-1">AI Suggestion</p>
                                                            <p className="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">Detected recurring invoice pattern for "Client X". Would you like to automate this schedule?</p>
                                                        </div>
                                                    </div>
                                                    <div className="flex gap-2">
                                                        <button className="flex-1 px-3 py-2 text-xs font-medium bg-violet-600 text-white rounded-lg hover:bg-violet-700 transition-colors">Automate</button>
                                                        <button className="flex-1 px-3 py-2 text-xs font-medium bg-slate-100 text-slate-700 dark:bg-zinc-800 dark:text-slate-300 rounded-lg hover:bg-slate-200 dark:hover:bg-zinc-700 transition-colors">Dismiss</button>
                                                    </div>
                                                </div>
                                            )}

                                            {activeFeature === 2 && (
                                                <div className="bg-white dark:bg-zinc-950 rounded-xl shadow-xl border border-slate-200 dark:border-white/10 overflow-hidden">
                                                    <div className="p-4 border-b border-slate-100 dark:border-white/5 flex justify-between items-center">
                                                        <span className="text-sm font-medium text-slate-900 dark:text-white">Recent Invoices</span>
                                                        <svg className="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" /></svg>
                                                    </div>
                                                    <div className="divide-y divide-slate-100 dark:divide-white/5">
                                                        {[1, 2, 3].map((i) => (
                                                            <div key={i} className="p-4 flex items-center justify-between hover:bg-slate-50 dark:hover:bg-zinc-900/50 transition-colors cursor-pointer group">
                                                                <div className="flex items-center gap-3">
                                                                    <div className="w-8 h-8 rounded bg-slate-100 dark:bg-zinc-800 flex items-center justify-center text-xs font-medium text-slate-500 group-hover:bg-blue-100 group-hover:text-blue-600 transition-colors">#{1020 + i}</div>
                                                                    <div>
                                                                        <p className="text-xs font-medium text-slate-900 dark:text-white">Tech Corp Ltd.</p>
                                                                        <p className="text-[10px] text-slate-500">RM {1250 * i}.00</p>
                                                                    </div>
                                                                </div>
                                                                <span className={`px-2 py-0.5 text-[10px] font-medium rounded-full ${i === 2 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'}`}>
                                                                    {i === 2 ? 'Pending' : 'Paid'}
                                                                </span>
                                                            </div>
                                                        ))}
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Stats or Trust Section */}
                    <div className="mx-auto max-w-7xl px-6 lg:px-8 py-20 relative z-10">
                        <dl className="grid grid-cols-1 gap-x-8 gap-y-16 text-center lg:grid-cols-3">
                            <div className="mx-auto flex max-w-xs flex-col gap-y-4 group">
                                <dt className="text-base leading-7 text-slate-600 dark:text-slate-400">Transactions processed</dt>
                                <dd className="order-first text-3xl font-semibold tracking-tight text-slate-900 dark:text-white sm:text-5xl group-hover:text-blue-600 transition-colors">100K+</dd>
                            </div>
                            <div className="mx-auto flex max-w-xs flex-col gap-y-4 group">
                                <dt className="text-base leading-7 text-slate-600 dark:text-slate-400">Uptime guarantee</dt>
                                <dd className="order-first text-3xl font-semibold tracking-tight text-slate-900 dark:text-white sm:text-5xl group-hover:text-blue-600 transition-colors">99.9%</dd>
                            </div>
                            <div className="mx-auto flex max-w-xs flex-col gap-y-4 group">
                                <dt className="text-base leading-7 text-slate-600 dark:text-slate-400">Active businesses</dt>
                                <dd className="order-first text-3xl font-semibold tracking-tight text-slate-900 dark:text-white sm:text-5xl group-hover:text-blue-600 transition-colors">5,000+</dd>
                            </div>
                        </dl>
                    </div>

                    {/* CTA Section */}
                    <div className="relative isolate mt-16 px-6 py-24 sm:mt-24 sm:py-32 lg:px-8 bg-blue-600 dark:bg-blue-900/20 overflow-hidden">
                        <div className="absolute inset-0 -z-10 bg-[radial-gradient(45rem_50rem_at_top,theme(colors.blue.400),theme(colors.blue.600))] dark:bg-[radial-gradient(45rem_50rem_at_top,theme(colors.blue.800),theme(colors.zinc.950))] opacity-50"></div>
                        <div className="mx-auto max-w-2xl text-center">
                            <h2 className="text-3xl font-bold tracking-tight text-white sm:text-4xl">
                                Ready to transform your invoicing?
                            </h2>
                            <p className="mx-auto mt-6 max-w-xl text-lg leading-8 text-blue-100">
                                Join thousands of businesses already saving time and ensuring compliance with Invoisync.
                            </p>
                            <div className="mt-10 flex items-center justify-center gap-x-6">
                                <Link
                                    href={route('register')}
                                    className="rounded-full bg-white px-8 py-3.5 text-sm font-semibold text-blue-600 shadow-sm hover:bg-blue-50 hover:scale-105 transition-all focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                                >
                                    Get started today
                                </Link>
                                <Link href={route('login')} className="text-sm font-semibold leading-6 text-white hover:text-blue-100 transition-colors group">
                                    Sign in to your account <span aria-hidden="true" className="inline-block transition-transform group-hover:translate-x-1">→</span>
                                </Link>
                            </div>
                        </div>
                    </div>
                </main>

                <footer className="bg-slate-50 dark:bg-zinc-950 border-t border-slate-200 dark:border-white/5">
                    <div className="mx-auto max-w-7xl px-6 py-12 md:flex md:items-center md:justify-between lg:px-8">
                        <div className="flex justify-center space-x-6 md:order-2">
                            {/* Social icons */}
                            <a href="#" className="text-slate-400 hover:text-blue-500 transition-colors hover:scale-110 transform">
                                <span className="sr-only">Twitter</span>
                                <svg className="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84" />
                                </svg>
                            </a>
                            <a href="#" className="text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors hover:scale-110 transform">
                                <span className="sr-only">GitHub</span>
                                <svg className="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path fillRule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clipRule="evenodd" />
                                </svg>
                            </a>
                        </div>
                        <div className="mt-8 md:order-1 md:mt-0">
                            <p className="text-center text-xs leading-5 text-slate-500">
                                &copy; {new Date().getFullYear()} Invoisync, Inc. All rights reserved.
                            </p>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
