# WordPress Popup Plugin Specification

## Overview

A simple, modern popup system for WordPress. Zero external dependencies (no Bootstrap, no ACF). The plugin provides the wrapper and behavior; the admin provides the content.

## Design Principles

- Minimal admin cognitive load
- Content cannot overflow boundaries
- Cookie-based display control
- Desktop/mobile content split (CSS-driven visibility)
- Vanilla JS, no framework dependencies

---

## Data Architecture

### Custom Post Type: `popup`

Each popup is a CPT entry with the following meta fields:

| Meta Key | Type | Description |
|----------|------|-------------|
| `_popup_trigger_type` | string | `exit_intent` \| `timeout` |
| `_popup_trigger_timeout` | int | Seconds delay (when trigger is `timeout`) |
| `_popup_cookie_key` | string | Unique identifier, auto-generated from slug |
| `_popup_cookie_expiry` | int | Days until cookie expires |
| `_popup_schedule_start` | date | Don't show before this date (optional) |
| `_popup_schedule_end` | date | Don't show after this date (optional) |
| `_popup_slides_desktop` | array | Array of HTML strings (rich editor content) |
| `_popup_slides_mobile` | array | Array of HTML strings (rich editor content) |
| `_popup_max_width` | int | Max width in pixels |
| `_popup_max_height` | string | Max height in pixels or `auto` |

---

## Admin UI

### Meta Boxes

**1. Trigger Settings**
- Radio: Exit Intent / Timeout
- Conditional: Timeout seconds input (shown when timeout selected)

**2. Cookie & Scheduling**
- Text: Cookie key (auto-populated from slug, editable)
- Number: Expiry days
- Date picker: Start date (optional)
- Date picker: End date (optional)

**3. Display Constraints**
- Number: Max width (px), default 600
- Number: Max height (px), default empty (auto)

**4. Desktop Slides**
- Repeater-style interface
- Each slide: wp_editor (rich)
- Add/remove/reorder slides

**5. Mobile Slides**
- Same structure as desktop
- Note to admin: "Leave empty to use desktop content on mobile"

---

## Frontend HTML Structure

```html
<div class="popup" 
     data-popup-id="{post_id}" 
     data-trigger="{trigger_type}" 
     data-timeout="{timeout_seconds}"
     style="--popup-max-width: {max_width}px; --popup-max-height: {max_height};">
  
  <div class="popup__overlay"></div>
  
  <div class="popup__container">
    <button class="popup__close" aria-label="Close">&times;</button>
    
    <!-- Desktop Content -->
    <div class="popup__content popup__content--desktop">
      <div class="popup__slides">
        <div class="popup__slide" data-index="0">{slide_html}</div>
        <div class="popup__slide" data-index="1">{slide_html}</div>
      </div>
      <nav class="popup__nav" aria-label="Slides">
        <div class="popup__dots">
          <button class="popup__dot active" data-index="0" aria-label="Slide 1"></button>
          <button class="popup__dot" data-index="1" aria-label="Slide 2"></button>
        </div>
      </nav>
    </div>
    
    <!-- Mobile Content -->
    <div class="popup__content popup__content--mobile">
      <!-- Same structure, different content -->
    </div>
    
  </div>
</div>
```

---

## CSS Specifications

### Core Constraints

```css
.popup__container {
  position: relative;
  max-width: var(--popup-max-width, 600px);
  max-height: var(--popup-max-height, 80vh);
  overflow: hidden;
  background: #fff;
  border-radius: 8px;
}

.popup__slides {
  display: flex;
  transition: transform 0.3s ease;
}

.popup__slide {
  flex: 0 0 100%;
  min-width: 0;
}

.popup__slide img {
  max-width: 100%;
  height: auto;
  display: block;
}
```

### Desktop/Mobile Visibility

```css
.popup__content--desktop { display: block; }
.popup__content--mobile { display: none; }

@media (max-width: 768px) {
  .popup__content--desktop { display: none; }
  .popup__content--mobile { display: block; }
}
```

### Navigation

- Dots only (no arrows)
- Hide `.popup__nav` when single slide
- Active dot styled distinctly

---

## JavaScript Behavior

### Initialization

1. On DOM ready, find all `.popup` elements
2. For each popup:
   - Check cookie (if set, skip)
   - Check schedule (if outside range, skip)
   - Attach trigger listener

### Triggers

**Exit Intent:**
- Listen for `mouseout` on document where `clientY < 0`
- Fire once per page load
- Show popup

**Timeout:**
- `setTimeout` based on `data-timeout` attribute
- Show popup after delay

### Popup Display

1. Add `is-open` class to `.popup`
2. Trap focus within container
3. Close on: overlay click, close button, Escape key

### Slides

- Track current index per popup
- Slide transition: translate `popup__slides` by `-{index * 100}%`
- Auto-advance every 5 seconds
- Pause auto-advance on hover
- Dot click jumps to slide

### Cookie

- On close: set cookie `popup_{cookie_key}=1`
- Expiry: `{expiry_days}` from set time
- Check cookie before showing

---

## PHP Logic

### Display Conditions

A popup renders on frontend when:
1. Post status is `publish`
2. Current date >= `schedule_start` (if set)
3. Current date <= `schedule_end` (if set)
4. Cookie check happens in JS (server doesn't see cookies on cached pages)

### Enqueue

- `popup.css` in header
- `popup.js` in footer, defer
- Inline JSON with popup config passed to JS

### Rendering

- Loop through published popups
- Render HTML at `wp_footer`
- Pass config via `data-*` attributes

---

## File Structure

```
popup/
├── popup.php                 # Plugin bootstrap, CPT registration
├── includes/
│   ├── class-admin.php       # Meta boxes, save handlers
│   ├── class-frontend.php    # Rendering, enqueue
│   └── class-fields.php      # Lightweight meta field helper
├── assets/
│   ├── css/
│   │   ├── popup.css         # Frontend styles
│   │   └── admin.css         # Admin meta box styles
│   └── js/
│       ├── popup.js          # Frontend behavior
│       └── admin.js          # Repeater UI for slides
└── templates/
    └── popup.php             # Popup HTML template
```

---

## Edge Cases

1. **Empty mobile slides**: Fall back to desktop content
2. **Single slide**: Hide navigation entirely
3. **No max-height set**: Use `80vh` as sensible default
4. **Cookie key collision**: Warn admin if slug matches existing popup
5. **Past end date**: Don't render popup at all (save bandwidth)
6. **Image overflow**: `object-fit: contain` + max dimensions enforced

---

## Future Considerations (Deferred)

- Manual trigger (click element X opens popup Y)
- Page/post targeting rules
- A/B testing variants
- Analytics integration

---

## Implementation Notes

- Use `wp_editor()` for rich slide content
- Slides repeater: simple JS add/remove, serialize as JSON
- Cookie handling in pure JS (no PHP cookie dependency for caching compatibility)
- All styles scoped under `.popup` namespace
- CSS custom properties for easy theming override
