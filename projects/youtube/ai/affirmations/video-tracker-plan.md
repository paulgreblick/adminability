# Video Tracker - Full Implementation Plan

## Overview

A production tracking system for faceless YouTube affirmation videos that allows you to:
- Track where each video is in the workflow
- Batch work by type (e.g., "record all audio today")
- See category-level progress at a glance
- Drill down to individual video details

---

## Workflow Phases & Steps

### Phase 1: WRITING
| Step | Description | Typical Time |
|------|-------------|--------------|
| Research | Topic research, gather affirmations, outline | - |
| First Draft | Write initial script | - |
| Review/Edit | Partner review, revisions | - |
| Final Script | Script approved and ready | - |

### Phase 2: PRODUCTION
| Step | Description | Typical Time |
|------|-------------|--------------|
| Slides | Create PowerPoint/Canva slides | - |
| Audio Record | Record voiceover/narration | - |
| Audio Edit | Clean up audio, add music | - |
| Video Compile | Combine slides + audio | - |
| Video Edit | Add effects, transitions, intro/outro | - |

### Phase 3: PUBLISHING
| Step | Description | Typical Time |
|------|-------------|--------------|
| Thumbnail | Design eye-catching thumbnail | - |
| Title | Write optimized title | - |
| Description | Write description with links, timestamps | - |
| Tags | Add keywords and tags | - |
| Upload | Upload to YouTube | - |
| Publish | Schedule or publish live | - |

---

## Views Needed

### 1. Dashboard (Home)
- Total videos in system
- Videos by phase: Writing / Production / Publishing / Complete
- Progress bar showing overall completion
- Quick stats: "12 videos need audio", "5 ready to upload"

### 2. By Category View
```
[▶] Self-Love Affirmations (3/8 complete) ████░░░░
[▼] Morning Routines (1/5 complete) ██░░░░░░
    ├── 5-Minute Morning Energy    [Writing: ✓] [Production: ◐] [Publishing: ○]
    ├── Gratitude Sunrise          [Writing: ✓] [Production: ○] [Publishing: ○]
    └── Confidence Boost           [Writing: ◐] [Production: ○] [Publishing: ○]
[▶] Abundance & Wealth (0/4 complete) ░░░░░░░░
[▶] Sleep & Relaxation (5/5 complete) ████████ ✓
```

### 3. By Work Type View (for batching)
Filter by current task needed:
- "Show me all videos that need Audio Recording"
- "Show me all videos that need Thumbnails"
- "Show me all videos ready to Upload"

```
AUDIO RECORDING NEEDED (7 videos)
┌─────────────────────────────────────────────────┐
│ Morning Energy          │ Self-Love      │ [Mark Done] │
│ Confidence Boost        │ Morning        │ [Mark Done] │
│ Money Magnet           │ Abundance      │ [Mark Done] │
│ ...                    │                │             │
└─────────────────────────────────────────────────┘
```

### 4. Video Detail View
Full detail for one video:
- Title, category, notes
- Link to Google Drive/Dropbox folder
- All 14 workflow steps with status
- Quick toggle buttons for each step
- Activity log (when steps were completed)

---

## Database Schema (Enhanced)

### video_projects (for multiple project types later)
```sql
- id
- name (e.g., "Affirmations", "Tutorials")
- description
- created_at
```

### video_categories
```sql
- id
- project_id (links to video_projects)
- name (e.g., "Self-Love", "Morning Routines")
- sort_order
- created_at
```

### videos
```sql
- id
- category_id
- title
- notes
- folder_link (Google Drive, etc.)

-- WRITING PHASE
- step_research ENUM('not_started','in_progress','complete')
- step_first_draft ENUM(...)
- step_review ENUM(...)
- step_final_script ENUM(...)

-- PRODUCTION PHASE
- step_slides ENUM(...)
- step_audio_record ENUM(...)
- step_audio_edit ENUM(...)
- step_video_compile ENUM(...)
- step_video_edit ENUM(...)

-- PUBLISHING PHASE
- step_thumbnail ENUM(...)
- step_title ENUM(...)
- step_description ENUM(...)
- step_tags ENUM(...)
- step_upload ENUM(...)
- step_publish ENUM(...)

- youtube_url (filled after publish)
- published_at
- created_at
- updated_at
```

### video_activity_log (optional - track who did what when)
```sql
- id
- video_id
- user_id
- step_name
- old_status
- new_status
- created_at
```

---

## UI Features

### Quick Actions
- Bulk status update: Select multiple videos, mark step as complete
- "Start my work session": Pick a work type, see only those videos
- "What's next?": Show bottlenecks (videos stuck at a step)

### Visual Indicators
- ○ Not started (gray)
- ◐ In progress (yellow)
- ● Complete (green)
- Phase badges: WRITING | PRODUCTION | PUBLISHING | DONE

### Filtering & Sorting
- By category
- By phase
- By specific step
- By date created
- By date updated
- Only show incomplete

---

## Video Categories (16 Topics)

1. **Morning Positive Energy** - Start your day with powerful positive affirmations
2. **Self Love** - Build self-worth and embrace who you are
3. **Abundance** - Attract abundance in all areas of life
4. **Manifestation** - Manifest your dreams and desires into reality
5. **Money** - Financial abundance and wealth consciousness
6. **Wealth** - Build lasting wealth and prosperity
7. **Success** - Achieve your goals and succeed in life
8. **Happiness** - Cultivate joy and lasting happiness
9. **Health** - Radiant health and physical wellbeing
10. **Peace and Calm** - Inner peace and tranquility
11. **Stress** - Release stress and find relief
12. **Anxiety** - Overcome anxiety and find calm
13. **Worry** - Let go of worry and embrace peace
14. **Overwhelm** - Manage overwhelm and regain control
15. **Healing from the Past** - Release the past and embrace healing
16. **Positive Life Changes** - Embrace change and transformation

---

## Implementation Phases

### Phase A: Core Functionality ✓ COMPLETE
- [x] Enhanced database schema with all 15 steps (3 phases)
- [x] 16 pre-populated affirmation categories
- [x] Video CRUD (create, read, update, delete)
- [x] By Category view with expandable rows and phase headers
- [x] By Work Type view with filtering by step
- [x] Video detail page with all 15 steps organized by phase
- [x] Progress tracking (overall and per-phase)
- [x] YouTube URL and folder link tracking
- [x] Auto-set published_at when all steps complete

### Phase B: Productivity Features (Future)
- [ ] Dashboard with stats and charts
- [ ] "What's next?" suggestions
- [ ] Bulk actions (mark multiple complete)
- [ ] Activity log

### Phase C: Polish (Future)
- [ ] Drag-and-drop reordering
- [ ] Keyboard shortcuts
- [ ] Mobile-responsive improvements
- [ ] Export to CSV

---

## Questions to Confirm

1. **Are these 14 steps correct?** Or do you want to add/remove any?

2. **Do you want sub-steps?** (e.g., Audio → Record, Edit, Add Music)

3. **Multiple projects?** Should we build for "Affirmations" only now, or set up for multiple video series from the start?

4. **Activity tracking?** Do you want to log when each step was completed and by whom?

5. **YouTube integration?** After publishing, do you want to paste the YouTube URL and have it stored?
