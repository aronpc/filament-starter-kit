# Git Workflow & Commits

## 📝 CRITICAL: Atomic Commits

**NEVER commit as "Claude" or "AI Assistant"** - commits are ALWAYS in developer's name.

### 🚨 CRITICAL: Commit Message Rules

**NEVER include the following in commit messages:**
- ❌ `🤖 Generated with [Claude Code](https://claude.com/claude-code)`
- ❌ `Co-Authored-By: Claude <noreply@anthropic.com>`
- ❌ Any AI-related attribution or signatures

**Commits must look like they were written by a human developer.**

### Commit Rules

1. **One commit = One logical change**
    - One feature = one commit
    - One bug fix = one commit
    - One refactor = one commit

2. **Message Format:**
   ```
   <type>: <short description>

   <detailed description if needed>

   - Bullet point of changes
   - Another change
   ```

3. **Commit Types:**
    - `feat:` New feature
    - `fix:` Bug fix
    - `refactor:` Code refactoring
    - `docs:` Documentation
    - `test:` Tests
    - `chore:` Maintenance

4. **When to Commit:**
    - After completing a feature
    - After fixing a bug
    - After adding tests
    - **After running `composer fix` successfully**

### Git Workflow

```bash
# 1. Make changes to code
# 2. Run quality checks
composer fix
composer test

# 3. Stage related files only
git add app/Http/Controllers/Owner/StaffController.php
git add resources/js/pages/owner/staff/

# 4. Commit with descriptive message
git commit -m "feat: Add StaffController with CRUD operations"

# 5. Update documentation in separate commit
git add IMPLEMENTATION.md
git commit -m "docs: Mark Staff Management as complete"
```

### Example Good Commits

```bash
# ✅ GOOD - atomic, focused
git commit -m "feat: Add Staff Management CRUD

Implemented complete staff management system:
- StaffController with index, create, store, edit, update, destroy
- React pages: index, create, edit, businesses
- StaffForm reusable component
- Translations in EN, ES, PT-BR
- Plan limit enforcement"

# ✅ GOOD - separate documentation
git commit -m "docs: Update implementation progress for Staff Management"
```

### Example Bad Commits

```bash
# ❌ BAD - too large, multiple features
git commit -m "Added everything"

# ❌ BAD - vague message
git commit -m "Fixed stuff"

# ❌ BAD - mixing features
git commit -m "Added staff management and fixed menu bug"
```
