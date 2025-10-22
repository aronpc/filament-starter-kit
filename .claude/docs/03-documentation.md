# Documentation Updates

## 📚 CRITICAL: Documentation Updates

**MANDATORY**: After implementing ANY feature, update docs in this order:

### 1. IMPLEMENTATION.md (ALWAYS)

**When:** After completing ANY task, feature, or sub-feature

**What:**

- Mark completed items: `[ ]` → `[x]`
- Update percentage progress
- Update "Status" field in sections
- Update "Progresso Geral" table
- Update "Total:" progress line
- Update "Próximos Passos"

**Example:**

```markdown
### 6.4 Staff Management ✅

- [x] Backend completo ✅
    - `StaffController` com CRUD completo (index, create, store, edit, update, destroy)

**Status:** Staff Management 100% completo - Backend, Forms, todas as páginas implementados.
```

### 2. CHECKPOINT.md (Major Milestones Only)

**When:** After major feature, version milestone, or end of work session

**What:**

- **Última Atualização:** Current date/time
- **Versão Atual:** Bump version (v1.8.0 → v1.9.0)
- **Progresso Total:** Update percentage
- **Onde Paramos:** Update "✅ Completo" section
- **Próximos Passos:** Update what's next
- **Métricas do Projeto:** Update counts

### 3. README.md (Only When Necessary)

**When:** ONLY for significant changes to:

- Project overview
- Installation steps
- Major features that change value proposition
- Tech stack changes
- New commands/usage patterns

### Documentation Workflow

```bash
# After implementing a feature:

# 1. ALWAYS update IMPLEMENTATION.md
# Mark completed tasks, update percentages

# 2. If major milestone, update CHECKPOINT.md
# Update version, progress, metrics

# 3. If significant change, update README.md (optional)
# Only if project overview changed

# 4. Commit documentation separately
git add IMPLEMENTATION.md CHECKPOINT.md
git commit -m "docs: Update implementation progress to v1.9.0 (58.5%)"
```

### Rules Summary

| File                  | When                             | Frequency     |
|-----------------------|----------------------------------|---------------|
| **IMPLEMENTATION.md** | After every feature/task         | ✅ ALWAYS      |
| **CHECKPOINT.md**     | After major features or sessions | ⚠️ FREQUENTLY |
| **README.md**         | Only project overview changes    | ℹ️ RARELY     |

### What NOT to Do

- ❌ Skip updating IMPLEMENTATION.md
- ❌ Update README.md for every small change
- ❌ Forget to update percentages and status fields
- ❌ Commit code and documentation together
