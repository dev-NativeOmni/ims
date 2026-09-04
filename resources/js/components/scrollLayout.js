export default () => ({
    scrollProgress: 0,
    scrollY: 0,
    activeDashboardTab: 'siswa', // for the simulator: 'siswa', 'guru', 'wali'
    isScrolled: false, // for sticky navbar contrast adjustments
    isDark: document.documentElement.classList.contains('dark'),
    
    init() {
        this.isDark = document.documentElement.classList.contains('dark');
        
        window.addEventListener('scroll', () => {
            this.scrollY = window.scrollY;
            this.isScrolled = window.scrollY > 20;
            
            // Calculate scroll progress percentage (0 to 1)
            const maxScroll = document.documentElement.scrollHeight - window.innerHeight;
            this.scrollProgress = maxScroll > 0 ? (window.scrollY / maxScroll) : 0;
        });
        
        console.log('IMS ScrollLayout component loaded with Theme Switcher support!');
    },

    toggleTheme() {
        this.isDark = !this.isDark;
        if (this.isDark) {
            document.documentElement.classList.add('dark');
            localStorage.setItem('theme', 'dark');
        } else {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('theme', 'light');
        }
    },
    
    trackMouse(event) {
        // Track client coordinates relative to the bounding box of the card
        const rect = event.currentTarget.getBoundingClientRect();
        const x = event.clientX - rect.left;
        const y = event.clientY - rect.top;
        
        // Update CSS properties directly on the hovered target
        event.currentTarget.style.setProperty('--mouse-x', `${x}px`);
        event.currentTarget.style.setProperty('--mouse-y', `${y}px`);
    }
});
