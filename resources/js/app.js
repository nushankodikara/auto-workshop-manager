document.addEventListener('DOMContentLoaded', () => {
    // Automatically wrap all tables in an overflow-x-auto container for horizontal scrollbars
    document.querySelectorAll('table').forEach(table => {
        // Skip tables already inside an overflow container
        let parent = table.parentElement;
        let isWrapped = false;
        while (parent && parent.tagName !== 'BODY') {
            if (parent.classList.contains('overflow-x-auto')) {
                isWrapped = true;
                break;
            }
            parent = parent.parentElement;
        }
        
        if (!isWrapped) {
            const wrapper = document.createElement('div');
            wrapper.className = 'overflow-x-auto w-full';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
    });
});
