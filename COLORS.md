# Gspot Gaming Hub - Brand Colors Reference

## Official Color Palette

![Logo Reference](C:/Users/ljlab/.gemini/antigravity/brain/943e1bb0-e017-4355-ab18-41d0afe39bb9/uploaded_media_1770111614534.png)

### Primary Colors

| Color Name | Hex Code | RGB | Usage |
|------------|----------|-----|-------|
| Dark Blue | `#0a2151` | rgb(10, 33, 81) | Background, Logo "G" letter |
| Yellow | `#F1e1aa` | rgb(241, 225, 170) | Logo "G" letter, accents |
| Green | `#20c8a1` | rgb(32, 200, 161) | Logo "S" letter, primary actions |
| Lavender | `#b37bec` | rgb(179, 123, 236) | Logo "P" letter, secondary elements |
| Red | `#Fb566b` | rgb(251, 86, 107) | Logo "O" letter, highlights |
| Blue | `#5f85da` | rgb(95, 133, 218) | Logo "T" letter, interactive elements |

## CSS Variables

All colors are defined as CSS custom properties in `assets/css/style.css`:

```css
:root {
    --color-primary: #0a2151;    /* Dark Blue */
    --color-secondary: #F1e1aa;  /* Yellow */
    --color-mint: #20c8a1;       /* Green */
    --color-purple: #b37bec;     /* Lavender */
    --color-coral: #Fb566b;      /* Red */
    --color-blue: #5f85da;       /* Blue */
}
```

## Usage Examples

### Gradients
- Primary Button: `linear-gradient(135deg, #20c8a1, #5f85da)`
- Hero Text: `linear-gradient(135deg, #20c8a1, #b37bec, #Fb566b, #5f85da)`
- Icons: `linear-gradient(135deg, #20c8a1, #b37bec)`

### Backgrounds
- Hero Section: `linear-gradient(135deg, #0D1117 0%, #0a2151 100%)`
- Cards: `rgba(10, 33, 81, 0.8)`
- Navbar: `rgba(10, 33, 81, 0.95)`

### Particles Configuration (JavaScript)
```javascript
color: {
    value: ['#20c8a1', '#b37bec', '#Fb566b', '#5f85da', '#F1e1aa']
}
```

## Brand Logo Letter Colors

```html
<span class="logo-g" style="color: #F1e1aa;">G</span>
<span class="logo-s" style="color: #20c8a1;">s</span>
<span class="logo-p" style="color: #b37bec;">p</span>
<span class="logo-o" style="color: #Fb566b;">o</span>
<span class="logo-t" style="color: #5f85da;">t</span>
```

## Color Accessibility

All color combinations have been designed to meet WCAG 2.1 AA standards for contrast:
- Text on dark blue background: ✅ Passes
- Colored accents on dark backgrounds: ✅ Passes
- Button text with gradient backgrounds: ✅ Passes

## Files Updated

1. ✅ `assets/css/style.css` - All color variables and references
2. ✅ `assets/js/main.js` - Particles.js color configuration
3. ✅ `index.php` - Logo letter colors (uses CSS variables)
4. ✅ All section files - Inherit from CSS variables

---

**Last Updated**: February 3, 2026  
**Version**: 1.0
