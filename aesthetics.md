# Project Aesthetics Overview

This document outlines the design aesthetics, including fonts, color scheme, icons, and other design aspects of the project.

## Core Technologies:

*   **CSS Framework:** The project uses **Tailwind CSS**, a utility-first framework. This means styling is applied directly to HTML elements using classes like `bg-slate-50` and `text-lg`.
*   **Icons:** **Font Awesome 6.5.0** is used for all icons, such as the dark mode toggle and navigation buttons. It's loaded via a CDN.
*   **JavaScript:** **Alpine.js** is used for light interactive elements, like managing the dark mode state. It's also loaded via a CDN.

## Visual Design Details:

### Color Scheme:

The application utilizes a sophisticated light and dark mode, primarily drawing from Tailwind CSS's 'slate' color palette.

#### Light Mode:
*   **Body Background:** `bg-slate-50` - **#f8fafc**
*   **Body Text:** `text-slate-900` - **#0f172a**
*   **Header Background:** `bg-white/80` - **#ffffff** with 80% opacity
*   **Header Border:** `border-slate-200` - **#e2e8f0**
*   **Primary Action Button (e.g., 'Submit Request'):**
    *   Background: `bg-slate-900` - **#0f172a**
    *   Text: `text-white` - **#ffffff**
*   **Secondary Action Button (e.g., 'Logout' in Admin):**
    *   Border: `border-slate-300` - **#cbd5e1**
    *   Background: `bg-white` - **#ffffff**
    *   Text: `text-slate-700` - **#334155**

#### Dark Mode:
*   **Body Background:** `dark:bg-slate-900` - **#0f172a**
*   **Body Text:** `dark:text-slate-100` - **#f1f5f9**
*   **Header Background:** `dark:bg-slate-800/80` - **#1e293b** with 80% opacity
*   **Header Border:** `dark:border-slate-700` - **#334155**
*   **Primary Action Button (e.g., 'Submit Request'):**
    *   Background: `dark:bg-slate-100` - **#f1f5f9**
    *   Text: `dark:text-slate-900` - **#0f172a**
*   **Secondary Action Button (e.g., 'Logout' in Admin):**
    *   Border: `dark:border-slate-600` - **#475569**
    *   Background: `dark:bg-slate-700` - **#334155**
    *   Text: `dark:text-slate-200` - **#e2e8f0**

### Typography:

*   **Fonts:** The application leverages Tailwind CSS's default sans-serif font stack. This ensures optimal rendering and performance across various operating systems by utilizing native system fonts. The typical font stack used is:
    ```
    ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji"
    ```
*   **Font Weights & Line Height:** For prominent text, such as titles and headings, classes like `font-extrabold` (extra bold weight) and `leading-tight` (reduced line height) are applied to give a strong and compact appearance.

### Assets:

*   **Public Site Logo:** `apps/public-form/public/assets/media_logo.png`
*   **Admin Site Logo:** A shield icon (`fa-shield-halved`) from Font Awesome is used.
*   **Favicon:** `apps/public-form/public/assets/favicon.ico`

This comprehensive overview should give you a clear understanding of the project's visual style.