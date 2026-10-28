.site-footer {
    max-width: 900px;
    margin: 3rem auto 0;
    padding: 1.5rem 1rem;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.75rem;
    color: #718096;
    font-size: 0.9rem;
}
.site-footer nav a {
    color: #4a5568;
    text-decoration: none;
    margin-left: 1.25rem;
}
.site-footer nav a:hover {
    color: #4299e1;
    text-decoration: underline;
}
@media (max-width: 640px) {
    .site-footer { flex-direction: column; text-align: center; }
    .site-footer nav a { margin: 0 0.6rem; }
}
