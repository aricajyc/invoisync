export default function ApplicationLogo({ variant = 'icon', className = '', ...props }) {
    const lightSrc = variant === 'word' ? '/images/light-word.png' : '/images/light.png';
    const darkSrc = variant === 'word' ? '/images/dark-word.png' : '/images/dark.png';

    return (
        <div className={`relative ${className}`} {...props}>
            {/* Light Mode Logo */}
            <img
                src={lightSrc}
                alt="Logo"
                className="block dark:hidden h-full w-auto object-contain"
            />

            {/* Dark Mode Logo */}
            <img
                src={darkSrc}
                alt="Logo"
                className="hidden dark:block h-full w-auto object-contain"
            />
        </div>
    );
}
