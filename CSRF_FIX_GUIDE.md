# CSRF 419 Error Fix Guide

## Problem
You're getting 419 "Page Expired" errors on POST requests in admin pages because CSRF tokens are missing or expired.

## Solution Pattern

### 1. Import usePage in your component
```javascript
import { useForm, usePage } from '@inertiajs/vue3';

const page = usePage();
```

### 2. Add CSRF token to your forms
```javascript
const form = useForm({
    // your existing fields
    field1: '',
    field2: '',
    // Add CSRF token
    _token: page.props.csrf_token,
});
```

### 3. Refresh token before submissions
```javascript
function submit() {
    // Refresh CSRF token before submission
    form._token = page.props.csrf_token;
    
    form.post(route('your.route'), {
        onSuccess: () => {
            // Refresh token after success
            form._token = page.props.csrf_token;
            // your success logic
        },
        onError: (errors) => {
            // Handle 419 errors by reloading page
            if (errors && (errors.message?.includes('419') || errors.status === 419)) {
                window.location.reload();
                return;
            }
            // your error handling
        },
    });
}
```

### 4. For router.delete calls
```javascript
function deleteItem(id) {
    router.delete(route('your.delete.route', id), {
        data: {
            _token: page.props.csrf_token
        },
        onError: (errors) => {
            if (errors && (errors.message?.includes('419') || errors.status === 419)) {
                window.location.reload();
                return;
            }
            // your error handling
        },
    });
}
```

## Files Already Fixed
- ✅ QRAddressUpload.vue
- ✅ Products.vue
- ✅ AdminLayout.vue (logout)

## Files That Need This Fix
Apply the above pattern to these admin pages:
- [ ] ContactSettings.vue
- [ ] DepositClients.vue
- [ ] SliderManager.vue
- [ ] TaskManager.vue
- [ ] Users.vue
- [ ] Withdrawals.vue
- [ ] WithdrawEdit.vue
- [ ] Any other admin pages with forms

## Quick Fix Steps for Each File
1. Add `usePage` import
2. Get page instance: `const page = usePage();`
3. Add `_token: page.props.csrf_token` to all useForm calls
4. Refresh token before each submission: `form._token = page.props.csrf_token;`
5. Add 419 error handling in onError callbacks
6. For router calls, add token in data object

This will prevent all 419 CSRF errors across your admin panel.