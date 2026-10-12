import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, Spinner, ToggleControl, TextControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { FormTokenField } from '@wordpress/components';

export default function Edit({ attributes, setAttributes }) {
    const { selectedTags, matchAllTags, showYearTimeline, showYearHeadings, maxPublications, showFeaturedImage } = attributes;

    // Fetch all available tags
    const { tags, isLoading } = useSelect((select) => {
        const { getEntityRecords, isResolving } = select('core');
        return {
            tags: getEntityRecords('taxonomy', 'post_tag', { per_page: -1 }),
            isLoading: isResolving('getEntityRecords', ['taxonomy', 'post_tag', { per_page: -1 }]),
        };
    }, []);

    // Convert tag IDs to tag names for display
    const selectedTagNames = selectedTags
        ? selectedTags.map((tagId) => {
            const tag = tags?.find((t) => t.id === tagId);
            return tag ? tag.name : '';
        }).filter(Boolean)
        : [];

    // Get all tag names for suggestions
    const tagNames = tags ? tags.map((tag) => tag.name) : [];

    // Handle tag selection changes
    const onTagsChange = (newTagNames) => {
        const newTagIds = newTagNames
            .map((name) => {
                const tag = tags?.find((t) => t.name.toLowerCase() === name.toLowerCase());
                return tag ? tag.id : null;
            })
            .filter(Boolean);

        setAttributes({ selectedTags: newTagIds });
    };

    return (
        <>
            <InspectorControls>
                <PanelBody title="Tag Settings">
                    {isLoading ? (
                        <Spinner />
                    ) : (
                        <>
                            <FormTokenField
                                label="Select Tags"
                                value={selectedTagNames}
                                suggestions={tagNames}
                                onChange={onTagsChange}
                                __experimentalExpandOnFocus
                                __experimentalShowHowTo={false}
                            />
                            <ToggleControl
                                label="Match all tags"
                                help={matchAllTags 
                                    ? "Posts must have ALL selected tags" 
                                    : "Posts must have AT LEAST ONE selected tag"
                                }
                                checked={matchAllTags}
                                onChange={(value) => setAttributes({ matchAllTags: value })}
                            />
                            <ToggleControl
                                label="Show year timeline"
                                help={"Display a sticky year navigator on the right"}
                                checked={showYearTimeline}
                                onChange={(value) => setAttributes({ showYearTimeline: value })}
                            />
                            <ToggleControl
                                label="Show year headings"
                                help={"Display the year as an h3 above each group in the list"}
                                checked={showYearHeadings}
                                onChange={(value) => setAttributes({ showYearHeadings: value })}
                            />
                            <TextControl
                                label="Max publications"
                                type="number"
                                min={0}
                                value={maxPublications}
                                onChange={(value) => setAttributes({ maxPublications: parseInt(value, 10) || 0 })}
                                help={maxPublications > 0
                                    ? `Show at most ${maxPublications} publications (most recent first)`
                                    : 'Show all publications'
                                }
                            />
                            <ToggleControl
                                label="Show featured image"
                                help={"Display the featured image on the left of the publication text when available"}
                                checked={showFeaturedImage}
                                onChange={(value) => setAttributes({ showFeaturedImage: value })}
                            />
                        </>
                    )}
                </PanelBody>
            </InspectorControls>

            <div {...useBlockProps()}>
                {selectedTags && selectedTags.length > 0 ? (
                    <>
                        <p>Selected tags: {selectedTagNames.join(', ')}</p>
                        <p><em>Match mode: {matchAllTags ? 'ALL tags' : 'ANY tag'}</em></p>
                    </>
                ) : (
                    <p>No tags selected. Use the sidebar to select tags.</p>
                )}
                <p>
                    <em>Year timeline: {showYearTimeline ? 'shown' : 'hidden'}</em>
                </p>
                <p>
                    <em>Year headings: {showYearHeadings ? 'shown' : 'hidden'}</em>
                </p>
                <p>
                    <em>Max publications: {maxPublications > 0 ? maxPublications : 'all'}</em>
                </p>
                <p>
                    <em>Featured image: {showFeaturedImage ? 'shown' : 'hidden'}</em>
                </p>
            </div>
        </>
    );
}
