import { useForm, usePage } from '@inertiajs/vue3';

export function useCsrfForm(data = {}) {
    const page = usePage();
    
    // Create form with CSRF token included
    const form = useForm({
        ...data,
        _token: page.props.csrf_token
    });
    
    // Helper function to refresh CSRF token
    const refreshCsrfToken = () => {
        form._token = page.props.csrf_token;
    };
    
    // Enhanced submit function that handles CSRF errors
    const submitWithCsrf = (method, url, options = {}) => {
        // Refresh token before submission
        refreshCsrfToken();
        
        const defaultOptions = {
            preserveState: true,
            preserveScroll: true,
            onError: (errors) => {
                // Handle 419 CSRF errors
                if (errors && (errors.message?.includes('419') || errors.status === 419)) {
                    console.warn('CSRF token expired, reloading page...');
                    window.location.reload();
                }
                // Call custom error handler if provided
                if (options.onError) {
                    options.onError(errors);
                }
            },
            onSuccess: (response) => {
                // Refresh token after successful submission
                refreshCsrfToken();
                // Call custom success handler if provided
                if (options.onSuccess) {
                    options.onSuccess(response);
                }
            }
        };
        
        const mergedOptions = { ...defaultOptions, ...options };
        
        // Call the appropriate method
        switch (method.toLowerCase()) {
            case 'post':
                return form.post(url, mergedOptions);
            case 'put':
                return form.put(url, mergedOptions);
            case 'patch':
                return form.patch(url, mergedOptions);
            case 'delete':
                return form.delete(url, mergedOptions);
            default:
                throw new Error(`Unsupported HTTP method: ${method}`);
        }
    };
    
    return {
        form,
        refreshCsrfToken,
        submitWithCsrf
    };
}